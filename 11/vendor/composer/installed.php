<?php return array(
    'root' => array(
        'name' => 'orbital/editor-suite',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => '7318789cf235048de54e094548cf8a76d222d56b',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'orbital/editor-suite' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '7318789cf235048de54e094548cf8a76d222d56b',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'wp-user-manager/wp-optionskit' => array(
            'pretty_version' => '1.1.2',
            'version' => '1.1.2.0',
            'reference' => '6253bda447991733bf8e19cb2123b41c666f3d62',
            'type' => 'library',
            'install_path' => __DIR__ . '/../wp-user-manager/wp-optionskit',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
