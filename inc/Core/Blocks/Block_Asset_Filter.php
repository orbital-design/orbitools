<?php

namespace Orbitools\Core\Blocks;

/**
 * Block Asset Filter
 *
 * Honours the per-block asset-disable toggles surfaced as virtual
 * settings on every `blocks`-category module (injected by
 * Modules_Controller::asset_toggles_for_blocks). Hooks
 * `block_type_metadata` so the block's manifest keys get stripped
 * before WordPress registers the block — that's enough to keep the
 * editor- and front-end-side enqueues from firing for the disabled
 * asset.
 *
 * Convention: an `orb/foo` block maps to the `foo-block` module
 * slug. Settings live in `orbitools_settings` under
 * `{module_slug}_disable_{frontend|editor}_{css|js}`.
 *
 * @package Orbitools
 * @since 3.2.0
 */
final class Block_Asset_Filter
{
    /**
     * setting key → block.json keys to remove when the toggle is on.
     *
     * @var array<string,array<int,string>>
     */
    private const ASSET_MAP = [
        'disable_frontend_css' => ['style', 'viewStyle'],
        'disable_frontend_js'  => ['script', 'viewScript'],
        'disable_editor_css'   => ['editorStyle'],
        'disable_editor_js'    => ['editorScript'],
    ];

    public function __construct()
    {
        \add_filter('block_type_metadata', [$this, 'filter_metadata']);
    }

    /**
     * @param mixed $metadata block.json data passed by WP.
     * @return mixed
     */
    public function filter_metadata($metadata)
    {
        if (!is_array($metadata) || empty($metadata['name']) || !is_string($metadata['name'])) {
            return $metadata;
        }
        if (strpos($metadata['name'], 'orb/') !== 0) {
            return $metadata;
        }

        $module_slug = substr($metadata['name'], strlen('orb/')) . '-block';

        $settings = \get_option('orbitools_settings', []);
        if (!is_array($settings)) {
            return $metadata;
        }

        foreach (self::ASSET_MAP as $setting_key => $manifest_keys) {
            $full_key = $module_slug . '_' . $setting_key;
            if (!empty($settings[$full_key])) {
                foreach ($manifest_keys as $k) {
                    unset($metadata[$k]);
                }
            }
        }

        return $metadata;
    }
}
