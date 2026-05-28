<?php

namespace Orbitools\Modules\User_Avatars;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Avatars module.
 *
 * Adds a Media Library-backed avatar picker to the WordPress user
 * profile screens (Users → Profile and Users → Edit User) and
 * routes WP's get_avatar() output through that picker first,
 * falling back to Gravatar afterwards. Optional "Disable Gravatar"
 * toggle swaps any URL that would otherwise hit gravatar.com for a
 * neutral SVG silhouette — useful for sites that don't want to
 * leak email hashes upstream.
 *
 * The picker UI lives entirely on the native profile screens; no
 * surface is added to this plugin's React admin.
 *
 * @package Orbitools
 * @since   3.2.0
 */
final class User_Avatars extends Module_Base
{
    /**
     * User meta key storing the chosen attachment ID. Underscored
     * prefix keeps it out of the generic Custom Fields metabox.
     */
    private const META_KEY = '_orbital_user_avatar_id';

    /**
     * Nonce action / field name for the profile-save handler.
     */
    private const NONCE_ACTION = 'orbital_user_avatar_save';
    private const NONCE_FIELD  = 'orbital_user_avatar_nonce';

    public function get_slug(): string
    {
        return 'user-avatars';
    }

    public function get_name(): string
    {
        return \__('User Avatars', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Local avatar uploads and Gravatar management for WordPress users.', 'orbitools');
    }

    public function init(): void
    {
        if ($this->get_setting('local_avatars_enabled', true)) {
            // Profile UI on Users → Profile and Users → Edit User.
            \add_action('show_user_profile', [$this, 'render_profile_field']);
            \add_action('edit_user_profile', [$this, 'render_profile_field']);
            \add_action('personal_options_update', [$this, 'save_profile_field']);
            \add_action('edit_user_profile_update', [$this, 'save_profile_field']);

            // Media Library + tiny inline picker script on the
            // profile screens.
            \add_action('admin_enqueue_scripts', [$this, 'enqueue_picker_assets']);

            // Inject the local avatar URL into get_avatar() output.
            // Priority 10 leaves room for other plugins to override
            // later if needed.
            \add_filter('pre_get_avatar_data', [$this, 'filter_avatar_data'], 10, 2);
        }

        if ($this->get_setting('disable_gravatar', false)) {
            $this->disable_gravatar();
        }
    }

    // -----------------------------------------------------------------
    // Profile-screen picker
    // -----------------------------------------------------------------

    public function enqueue_picker_assets(string $hook_suffix): void
    {
        if (!in_array($hook_suffix, ['profile.php', 'user-edit.php'], true)) {
            return;
        }
        \wp_enqueue_media();
        // The script doesn't actually use jQuery, but jquery-core is
        // the earliest reliably-present handle in wp-admin and gives
        // us a stable place to attach an inline footer script.
        \wp_add_inline_script('jquery-core', $this->picker_script(), 'after');
    }

    /**
     * @param \WP_User $user
     */
    public function render_profile_field($user): void
    {
        if (!$user instanceof \WP_User) {
            return;
        }

        $attachment_id = (int) \get_user_meta($user->ID, self::META_KEY, true);
        // `force_default` so we get the *generic* avatar (Gravatar
        // mystery person / blank SVG) as the placeholder rather
        // than whatever WP would normally serve for this user.
        $default_url = \get_avatar_url($user->ID, ['size' => 96, 'force_default' => true]);
        $current_url = $attachment_id > 0
            ? (\wp_get_attachment_image_url($attachment_id, [96, 96]) ?: $default_url)
            : $default_url;
        ?>
        <h2><?php echo \esc_html__('Profile picture', 'orbitools'); ?></h2>
        <table class="form-table" role="presentation">
            <tr class="orbital-avatar-field-row">
                <th><label for="orbital-user-avatar-id"><?php echo \esc_html__('Avatar', 'orbitools'); ?></label></th>
                <td>
                    <div class="orbital-avatar-field" data-default-avatar="<?php echo \esc_attr($default_url); ?>">
                        <img class="orbital-avatar-field__preview"
                             src="<?php echo \esc_url($current_url); ?>"
                             alt=""
                             width="96"
                             height="96" />
                        <div class="orbital-avatar-field__actions">
                            <input type="hidden"
                                   id="orbital-user-avatar-id"
                                   name="<?php echo \esc_attr(self::META_KEY); ?>"
                                   value="<?php echo \esc_attr((string) $attachment_id); ?>" />
                            <button type="button" class="button orbital-avatar-field__pick">
                                <?php echo \esc_html__('Choose image', 'orbitools'); ?>
                            </button>
                            <button type="button"
                                    class="button-link orbital-avatar-field__remove"
                                    <?php echo $attachment_id > 0 ? '' : 'hidden'; ?>>
                                <?php echo \esc_html__('Remove', 'orbitools'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php echo \esc_html__('Pick any image from the Media Library. If unset, Gravatar (or the configured fallback) is used.', 'orbitools'); ?>
                        </p>
                    </div>
                    <?php \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD, false); ?>
                </td>
            </tr>
        </table>
        <style>
            .orbital-avatar-field__preview {
                border-radius: 50%;
                display: block;
                margin-bottom: 12px;
                object-fit: cover;
            }
            .orbital-avatar-field__actions {
                align-items: center;
                display: flex;
                gap: 8px;
                margin-bottom: 6px;
            }
        </style>
        <?php
    }

    public function save_profile_field(int $user_id): void
    {
        if (!\current_user_can('edit_user', $user_id)) {
            return;
        }
        if (!isset($_POST[self::NONCE_FIELD])) {
            return;
        }
        $nonce = \sanitize_text_field(\wp_unslash((string) $_POST[self::NONCE_FIELD]));
        if (!\wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        $attachment_id = isset($_POST[self::META_KEY]) ? (int) $_POST[self::META_KEY] : 0;
        if ($attachment_id > 0 && \wp_attachment_is_image($attachment_id)) {
            \update_user_meta($user_id, self::META_KEY, $attachment_id);
        } else {
            \delete_user_meta($user_id, self::META_KEY);
        }
    }

    // -----------------------------------------------------------------
    // Avatar resolution
    // -----------------------------------------------------------------

    /**
     * Replace WP's resolved avatar URL with the user's local
     * upload, if one is set.
     *
     * @param array<string,mixed> $args
     * @param mixed               $id_or_email
     * @return array<string,mixed>
     */
    public function filter_avatar_data(array $args, $id_or_email): array
    {
        $user_id = $this->resolve_user_id($id_or_email);
        if ($user_id === 0) {
            return $args;
        }

        $attachment_id = (int) \get_user_meta($user_id, self::META_KEY, true);
        if ($attachment_id <= 0) {
            return $args;
        }

        $size = isset($args['size']) ? (int) $args['size'] : 96;
        // 2× the requested size so the image stays sharp on HiDPI
        // displays; WP picks the closest registered size.
        $url = \wp_get_attachment_image_url($attachment_id, [$size * 2, $size * 2]);
        if (!$url) {
            $url = \wp_get_attachment_url($attachment_id);
        }
        if (is_string($url) && $url !== '') {
            $args['url']          = $url;
            $args['found_avatar'] = true;
        }
        return $args;
    }

    /**
     * Resolve whatever WP gave us — user ID, email, WP_User, WP_Post,
     * WP_Comment — to a numeric user ID. Returns 0 when no user can
     * be associated (the caller treats that as "no local avatar").
     *
     * @param mixed $id_or_email
     */
    private function resolve_user_id($id_or_email): int
    {
        if (is_numeric($id_or_email)) {
            return (int) $id_or_email;
        }
        if (is_string($id_or_email) && strpos($id_or_email, '@') !== false) {
            $user = \get_user_by('email', $id_or_email);
            return $user instanceof \WP_User ? (int) $user->ID : 0;
        }
        if ($id_or_email instanceof \WP_User) {
            return (int) $id_or_email->ID;
        }
        if ($id_or_email instanceof \WP_Post) {
            return (int) $id_or_email->post_author;
        }
        if ($id_or_email instanceof \WP_Comment) {
            return (int) $id_or_email->user_id;
        }
        return 0;
    }

    // -----------------------------------------------------------------
    // Optional Gravatar suppression
    // -----------------------------------------------------------------

    private function disable_gravatar(): void
    {
        \add_filter('avatar_defaults', [$this, 'remove_gravatar_defaults']);
        \add_filter('get_avatar_url', [$this, 'replace_gravatar_url'], 10, 3);
        \add_filter('user_profile_picture_description', [$this, 'remove_gravatar_description']);
    }

    /**
     * @param array<string,string> $defaults
     * @return array<string,string>
     */
    public function remove_gravatar_defaults($defaults): array
    {
        return ['blank' => \__('Blank', 'orbitools')];
    }

    /**
     * If the URL still points at gravatar.com after every other filter
     * has run (i.e. no local avatar), swap it for our blank SVG.
     *
     * @param string $url
     * @param mixed  $id_or_email
     * @param array<string,mixed> $args
     */
    public function replace_gravatar_url($url, $id_or_email, $args): string
    {
        if (is_string($url) && strpos($url, 'gravatar.com') !== false) {
            $size = isset($args['size']) ? (int) $args['size'] : 96;
            return $this->blank_avatar_data_url($size);
        }
        return (string) $url;
    }

    public function remove_gravatar_description($description): string
    {
        return \__('Pick an avatar from the Media Library. Otherwise a blank silhouette is shown.', 'orbitools');
    }

    private function blank_avatar_data_url(int $size): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100">'
             . '<rect width="100" height="100" fill="#e3e8eb"/>'
             . '<circle cx="50" cy="40" r="16" fill="#bcc6cd"/>'
             . '<path d="M20 92c0-18 13.4-32 30-32s30 14 30 32z" fill="#bcc6cd"/>'
             . '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    // -----------------------------------------------------------------
    // Inline Media Library picker (vanilla JS, no jQuery)
    // -----------------------------------------------------------------

    private function picker_script(): string
    {
        return <<<'JS'
(function () {
    function init() {
        var fields = document.querySelectorAll('.orbital-avatar-field');
        if (fields.length === 0 || typeof wp === 'undefined' || !wp.media) {
            return;
        }
        fields.forEach(function (field) {
            var pickBtn    = field.querySelector('.orbital-avatar-field__pick');
            var removeBtn  = field.querySelector('.orbital-avatar-field__remove');
            var input      = field.querySelector('input[type="hidden"]');
            var preview    = field.querySelector('.orbital-avatar-field__preview');
            var defaultUrl = field.getAttribute('data-default-avatar') || '';
            var frame;

            pickBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!frame) {
                    frame = wp.media({
                        title: 'Select avatar',
                        button: { text: 'Use this image' },
                        library: { type: 'image' },
                        multiple: false
                    });
                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        input.value = attachment.id;
                        var sized = attachment.sizes && (attachment.sizes.thumbnail || attachment.sizes.medium);
                        preview.src = sized ? sized.url : attachment.url;
                        removeBtn.hidden = false;
                    });
                }
                frame.open();
            });

            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                input.value = '0';
                preview.src = defaultUrl;
                removeBtn.hidden = true;
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
JS;
    }
}
