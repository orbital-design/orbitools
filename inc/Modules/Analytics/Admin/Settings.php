<?php

/**
 * Analytics Settings
 *
 * Stub for any future Analytics-specific setting hooks. The AdminKit-
 * era field-definition / admin-structure helpers were removed in v3
 * Phase 7; the React admin reads the field schema from module.json.
 *
 * @package    Orbitools
 * @subpackage Modules/Analytics/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Analytics\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    /**
     * Initialize settings functionality.
     *
     * Called by Analytics::init(). No-op for now — kept so the call
     * site doesn't need a feature flag if module-specific settings
     * wiring (REST sub-endpoints, post-save hooks) lands later.
     */
    public static function init(): void
    {
    }
}
