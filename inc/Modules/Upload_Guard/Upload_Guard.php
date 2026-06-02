<?php

declare(strict_types=1);

namespace Orbitools\Modules\Upload_Guard;

use Orbitools\Core\Abstracts\Module_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Upload Guard — block theme zip uploads on local environments.
 *
 * Local installs are a common vector for accidental theme overwrites:
 * drag the wrong .zip into Appearance → Themes → Add New → Upload Theme
 * and you've just overwritten in-progress work. This module:
 *
 *   * Hooks `wp_handle_upload_prefilter` to reject any upload whose
 *     `action` request var is `upload-theme`.
 *   * Hides the Upload-Theme button on themes.php / theme-install.php
 *     as a visual safeguard.
 *
 * Bails out on any environment other than `local` (per WP's
 * `WP_ENVIRONMENT_TYPE`), so leaving the module enabled costs zero on
 * staging / production.
 *
 * Ported from the dream-and-leap-sage theme's
 * `App\Providers\UploadGuardServiceProvider`.
 *
 * @package Orbitools
 * @since   1.0.0
 */
final class Upload_Guard extends Module_Base
{
    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'upload-guard';
    }

    public function get_name(): string
    {
        return \__('Upload Guard', 'orbitools');
    }

    public function get_description(): string
    {
        return \__('Blocks theme .zip uploads on local environments to prevent accidental overwrites of in-progress theme work.', 'orbitools');
    }

    public function init(): void
    {
        if (\wp_get_environment_type() !== 'local') {
            return;
        }

        \add_filter('wp_handle_upload_prefilter', [$this, 'block_theme_upload']);
        \add_action('admin_head-themes.php', [$this, 'hide_upload_button']);
        \add_action('admin_head-theme-install.php', [$this, 'hide_upload_button']);
    }

    public function get_default_settings(): array
    {
        return [];
    }

    /**
     * Reject the upload if its request action is `upload-theme`. Adding
     * an `error` key to the upload-file array short-circuits the rest of
     * `wp_handle_upload`, so the file is never saved.
     *
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function block_theme_upload(array $file): array
    {
        $action = isset($_REQUEST['action']) ? \sanitize_key((string) \wp_unslash($_REQUEST['action'])) : '';

        if ($action === 'upload-theme') {
            $file['error'] = \__('Theme uploads are disabled on local environments.', 'orbitools');
        }

        return $file;
    }

    /**
     * Inline CSS that hides every Upload-Theme entry point on the
     * theme admin screens.
     */
    public function hide_upload_button(): void
    {
        echo '<style>.upload-theme, .themes-php .add-new-theme, .themes-php .page-title-action, .theme-install-php .page-title-action { display: none !important; }</style>';
    }
}
