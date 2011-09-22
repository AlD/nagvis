<?php

function listMapImages($CORE) {
    $options = $CORE->getAvailableBackgroundImages();
    array_unshift($options, 'none');
    return $options;
}

function listIconsets($CORE) {
    return $CORE->getAvailableIconsets();
}

function listLineTypes($CORE) {
    return Array(
        '10' => '------><------',
        '11' => '-------------->',
        '12' => '---------------',
        '13' => '--%--><--%--',
        '14' => '--%+BW-><-%+BW--',
    );
}

function listHoverChildSorters() {
    return Array(
        'a' => l('Alphabetically'),
        's' => l('State'),
    );
}

function listHoverChildOrders() {
    return Array(
        'asc'  => l('Ascending'),
        'desc' => l('Descending'),
    );
}

function listGadgetTypes() {
    return Array(
        'img'  => l('Image'),
        'html' => l('HTML Code'),
    );
}

function listGadgets($CORE) {
    return $CORE->getAvailableGadgets();
}

function listHoverTemplates($CORE) {
    return $CORE->getAvailableHoverTemplates();
}

function listContextTemplates($CORE) {
    return $CORE->getAvailableContextTemplates();
}

function listViewTypesService($CORE) {
    return Array('icon', 'line', 'gadget');
}

function listViewTypes($CORE) {
    return Array('icon', 'line');
}

function getObjectNames($type, $CORE, $MAPCFG, $objId) {
    $backendId = $MAPCFG->getValue($objId, 'backend_id');

    // Initialize the backend
    $BACKEND = new CoreBackendMgmt($CORE);
    $BACKEND->checkBackendExists($backendId, true);
    $BACKEND->checkBackendFeature($backendId, 'getObjects', true);

    $name1 = ($type === 'service' ? $MAPCFG->getValue($objId, 'host_name') : '');

    // Read all objects of the requested type from the backend
    $aRet = Array('');
    $objs = $BACKEND->getBackend($backendId)->getObjects($type, $name1, '');
    foreach($objs AS $obj) {
        if($type !== 'service')
            $aRet[] = $obj['name1'];
        else
            $aRet[] = $obj['name2'];
    }

    return $aRet;
}

function listHostNames($CORE, $MAPCFG, $objId) {
    return getObjectNames('host', $CORE, $MAPCFG, $objId);
}

function listHostgroupNames($CORE, $MAPCFG, $objId) {
    return getObjectNames('hostgroup', $CORE, $MAPCFG, $objId);
}

function listServiceNames($CORE, $MAPCFG, $objId) {
    return getObjectNames('service', $CORE, $MAPCFG, $objId);
}

function listServicegroupNames($CORE, $MAPCFG, $objId) {
    return getObjectNames('servicegroup', $CORE, $MAPCFG, $objId);
}

function listTemplateNames($CORE) {
    return Array();
}

function listShapes($CORE) {
    return $CORE->getAvailableShapes();
}

function listBackendIds($CORE) {
    return $CORE->getDefinedBackends();
}

$mapConfigVars = Array(
    'type' => Array(
        'must' => 0,
        'match' => MATCH_OBJECTTYPE,
        'field_type' => 'hidden',
    ),
    'object_id' => Array(
        'must' => 0,
        'match' => MATCH_OBJECTID,
        'field_type' => 'hidden',
    ),
    'allowed_for_config' => Array(
        'must' => 0,
        'deprecated' => true,
        'match' => MATCH_STRING,
    ),
    'allowed_user' => Array(
        'must' => 0,
        'deprecated' => true,
        'match' => MATCH_STRING,
    ),
    'map_image' => Array(
        'must'       => 0,
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL_NONE,
        'field_type' => 'dropdown',
        'list'       => 'listMapImages',
    ),
    'alias' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING),
    'backend_id' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'backend'),
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listBackendIds',
    ),
    'background_color' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'backgroundcolor'),
        'match' => MATCH_COLOR),
    'default_params' => Array(
        'must' => 0,
        'default' => cfg('automap', 'defaultparams'),
        'match' => MATCH_STRING_URL,
        'field_type' => 'hidden'),
    'parent_map' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_MAP_NAME_EMPTY),

    'context_menu' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'contextmenu'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'context_template' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'contexttemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'context_menu',
        'depends_value' => '1',
        'list'          => 'listContextTemplates',
    ),

    'event_background' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventbackground'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),

    'event_highlight' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlight'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_highlight_interval' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightinterval'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'),
    'event_highlight_duration' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventhighlightduration'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_highlight',
        'depends_value' => '1'),

    'event_log' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlog'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_log_level' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventloglevel'),
        'match' => MATCH_STRING_NO_SPACE,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_events' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlogevents'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_height' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventlogheight'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'event_log',
        'depends_value' => '1'),
    'event_log_hidden' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventloghidden'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'event_log',
        'depends_value' => '1'),

    'event_scroll' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventscroll'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'event_sound' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'eventsound'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),

    'exclude_members' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ),
    'exclude_member_states' => Array(
        'must'       => 0,
        'default'    => '',
        'match'      => MATCH_REGEX,
    ),

    'grid_show' => Array(
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_show')),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'grid_color' => Array(
        'must' => 0,
        'default' => cfg('wui', 'grid_color'),
        'match' => MATCH_COLOR,
        'depends_on' => 'grid_show',
        'depends_value' => '1'),
    'grid_steps' => Array(
        'must' => 0,
        'default' => intval(cfg('wui', 'grid_steps')),
        'match' => MATCH_INTEGER,
        'depends_on' => 'grid_show',
        'depends_value' => '1'),

    'header_menu' => Array(
        'must' => 0,
        'default'        => cfg('defaults', 'headermenu'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'boolean'),
    'header_template' => Array(
        'must' => 0,
        'default'        => cfg('defaults', 'headertemplate'),
        'match'          => MATCH_STRING_NO_SPACE,
        'field_type'     => 'dropdown',
        'depends_on'     => 'header_menu',
        'depends_value'  => '1'),
    'header_fade' => Array(
        'must' => 0,
        'default'        => cfg('defaults', 'headerfade'),
        'match'          => MATCH_BOOLEAN,
        'field_type'     => 'boolean',
        'depends_on'     => 'header_menu',
        'depends_value'  => '1'),

    'hover_menu' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hovermenu'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'hover_delay' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverdelay'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_template' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hovertemplate'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverTemplates',
     ),
    'hover_timeout' => Array(
        'must' => 0,
        'deprecated' => true,
        'default' => cfg('defaults', 'hovertimeout'),
        'match' => MATCH_INTEGER,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'
    ),
    'hover_url' => Array(
        'must' => 0,
        'match' => MATCH_STRING_URL,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'
    ),
    'hover_childs_show' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildsshow'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_childs_limit' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hoverchildslimit'),
        'match' => MATCH_INTEGER_PRESIGN,
        'depends_on' => 'hover_menu',
        'depends_value' => '1'),
    'hover_childs_order' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildsorder'),
        'match'         => MATCH_ORDER,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildOrders',
    ),
    'hover_childs_sort' => Array(
        'must'          => 0,
        'default'       => cfg('defaults', 'hoverchildssort'),
        'match'         => MATCH_STRING_NO_SPACE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'hover_menu',
        'depends_value' => '1',
        'list'          => 'listHoverChildSorters',
    ),

    'iconset' => Array(
        'must'       => 0,
        'default'    => cfg('defaults', 'icons'),
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listIconsets',
    ),
    'line_type' => Array(
        'must'          => 0,
        'match'         => MATCH_LINE_TYPE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
        'list'          => 'listLineTypes',
    ),
    // At the moment this value is only used for the automap to controll
    // the style of the connector arrow. But maybe this attribute can be
    // used on regular maps for line objects too.
    'line_arrow' => Array(
        'must' => 0,
        'default' => 'forward',
        'match' => MATCH_LINE_ARROW,
        'depends_on' => 'view_type',
        'depends_value' => 'line'),
    'line_color' => Array(
        'must' => 0,
        'default' => '#ffffff',
        'match' => MATCH_COLOR
    ),
    'line_color_border' => Array(
        'must' => 0,
        'default' => '#000000',
        'match' => MATCH_COLOR
    ),
    'line_cut' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_show' => Array(
        'must'          => 0,
        'default'       => '1',
        'match'         => MATCH_BOOLEAN,
        'field_type'    => 'boolean',
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_pos_in' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line',
    ),
    'line_label_pos_out' => Array(
        'must'          => 0,
        'default'       => '0.5',
        'match'         => MATCH_FLOAT,
        'depends_on'    => 'view_type',
        'depends_value' => 'line'
    ),
    'line_width' => Array(
        'must' => 0,
        'default' => '3',
        'match' => MATCH_INTEGER,
        'depends_on' => 'view_type',
        'depends_value' => 'line'),
    'line_weather_colors' => Array(
        'must' => 0,
        'default' => '10:#8c00ff,25:#2020ff,40:#00c0ff,55:#00f000,70:#f0f000,85:#ffc000,100:#ff0000',
        'match' => MATCH_WEATHER_COLORS,
        'depends_on' => 'view_type',
        'depends_value' => 'line'),

    'in_maintenance' => Array(
        'must'       => 0,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),

    'label_show' => Array(
        'must'       => 0,
        'default'    => '0',
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),
    'label_text' => Array(
        'must' => 0,
        'default' => '[name]',
        'match' => MATCH_ALL,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_x' => Array(
        'must' => 0,
        'default' => '-20',
        'match' => MATCH_INTEGER_PRESIGN,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_y' => Array(
        'must' => 0,
        'default' => '+20',
        'match' => MATCH_INTEGER_PRESIGN,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_width' => Array(
        'must' => 0,
        'default' => 'auto',
        'match' => MATCH_TEXTBOX_WIDTH,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_background' => Array(
        'must' => 0,
        'default' => 'transparent',
        'match' => MATCH_COLOR,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_border' => Array(
        'must' => 0,
        'default' => '#000000',
        'match' => MATCH_COLOR,
        'depends_on' => 'label_show',
        'depends_value' => '1'),
    'label_style' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
        'depends_on' => 'label_show',
        'depends_value' => '1'),

    'only_hard_states' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'onlyhardstates'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'recognize_services' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'recognizeservices'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'show_in_lists' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'showinlists'),
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'show_in_multisite' => Array(
        'must' => 0,
        'default'    => cfg('defaults', 'showinmultisite'),
        'match'      => MATCH_BOOLEAN,
        'field_type' => 'boolean'),
    'stylesheet' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'stylesheet'),
        'match' => MATCH_STRING_NO_SPACE),
    'url_target' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'urltarget'),
        'match' => MATCH_STRING_NO_SPACE),

    'x' => Array(
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ),
    'y' => Array(
        'must' => 1,
        'match' => MATCH_COORDS_MULTI
    ),
    'z' => Array(
        'must' => 0,
        'default' => 10,
        'match' => MATCH_INTEGER
    ),
    'view_type' => Array(
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypes',
    ),
    'url' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_URL_EMPTY,
    ),
    'use' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_NO_SPACE,
    ),

    // HOST SPECIFIC OPTIONS

    'host_name' => Array(
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostNames',
    ),
    'host_url' => Array(
        'must'    => 0,
        'default' => cfg('defaults', 'hosturl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ),

    // HOSTGROUP SPECIFIC OPTIONS

    'hostgroup_name' => Array(
        'must' => 1,
        'match' => MATCH_STRING,
        'field_type' => 'dropdown',
        'list' => 'listHostgroupNames',
    ),
    'hostgroup_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'hostgroupurl'),
        'match' => MATCH_STRING_URL_EMPTY
    ),

    // SERVICE SPECIFIC OPTIONS

    'service_description' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listServiceNames',
    ),
    'service_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'serviceurl'),
        'match'  => MATCH_STRING_URL_EMPTY,
    ),
    'service_view_type' => Array(
        'must'          => 0,
        'default'       => 'icon',
        'match'         => MATCH_VIEW_TYPE_SERVICE,
        'field_type'    => 'dropdown',
        'list'          => 'listViewTypesService',
    ),
    'gadget_url' => Array(
        'must'          => 0,
        'match'         => MATCH_STRING_URL,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => '',
        'list'          => 'listGadgets',
    ),
    'gadget_type' => Array(
        'must'          => 0,
        'match'         => MATCH_GADGET_TYPE,
        'field_type'    => 'dropdown',
        'depends_on'    => 'view_type',
        'depends_value' => 'gadget',
        'default'       => 'img',
        'list'          => 'listGadgetTypes',
    ),
    'gadget_scale' => Array('must' => 0,
        'default' => 100,
        'match' => MATCH_INTEGER,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ),
    'gadget_opts' => Array('must' => 0,
        'default' => '',
        'match' => MATCH_GADGET_OPT,
        'depends_on' => 'view_type',
        'depends_value' => 'gadget',
    ),

    // SERVICEGROUP SPECIFIC OPTIONS

    'servicegroup_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING,
        'field_type' => 'dropdown',
        'list'       => 'listServicegroupNames',
    ),
    'servicegroup_url' => Array(
        'must'    => 0,
        'default' => cfg('defaults', 'servicegroupurl'),
        'match'   => MATCH_STRING_URL_EMPTY,
    ),

    // MAP SPECIFIC OPTIONS

    'map_name' => Array(
        'must'       => 1,
        'match'      => MATCH_STRING_NO_SPACE,
        'field_type' => 'dropdown',
        'list'       => 'listMapNames',
    ),
    'map_url' => Array(
        'must' => 0,
        'default' => cfg('defaults', 'mapurl'),
        'match' => MATCH_STRING_URL_EMPTY,
    ),

    // TEXTBOX SPECIFIC OPTIONS

    'text' => Array(
        'must' => 1,
        'match' => MATCH_ALL,
    ),
    'border_color' => Array(
        'must' => 0,
        'default' => '#000000',
        'match' => MATCH_COLOR,
    ),
    'style' => Array(
        'must' => 0,
        'default' => '',
        'match' => MATCH_STRING_STYLE,
    ),
    'h' => Array(
        'must' => 0,
        'default' => 'auto',
        'match' => MATCH_TEXTBOX_HEIGHT,
    ),
    'w' => Array(
        'must' => 1,
        'match' => MATCH_TEXTBOX_WIDTH,
    ),

    // SHAPE SPECIFIC OPTIONS

    'icon' => Array(
        'must'       => 1,
        'match'      => MATCH_PNG_GIF_JPG_FILE_OR_URL,
        'field_type' => 'dropdown',
        'list'       => 'listShapes',
    ),
    'enable_refresh' => Array(
        'must' => 0,
        'default' => 0,
        'match' => MATCH_BOOLEAN,
        'field_type' => 'boolean',
    ),

    // TEMPLATE SPECIFIC OPTIONS

    'name' => Array(
        'must'  => 1,
        'match' => MATCH_STRING_NO_SPACE,
        'list'  => 'listTemplateNames',
    ),
);

//
// map configuration variable registration
//

$mapConfigVarMap['global'] = Array(
    'type' => null,
    'object_id' => null,
    'allowed_for_config' => null,
    'allowed_user' => null,
    'map_image' => null,
    'alias' => null,
    'backend_id' => null,
    'background_color' => null,
    'default_params' => null,
    'parent_map' => null,
    'context_menu' => null,
    'context_template' => null,
    'event_background' => null,
    'event_highlight' => null,
    'event_highlight_interval' => null,
    'event_highlight_duration' => null,
    'event_log' => null,
    'event_log_level' => null,
    'event_log_events' => null,
    'event_log_height' => null,
    'event_log_hidden' => null,
    'event_scroll' => null,
    'event_sound' => null,
    'exclude_members' => null,
    'exclude_member_states' => null,
    'grid_show' => null,
    'grid_color' => null,
    'grid_steps' => null,
    'header_menu' => null,
    'header_template' => null,
    'header_fade' => null,
    'hover_menu' => null,
    'hover_delay' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_childs_show' => null,
    'hover_childs_limit' => null,
    'hover_childs_order' => null,
    'hover_childs_sort' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'in_maintenance' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'recognize_services' => null,
    'show_in_lists' => null,
    'show_in_multisite' => null,
    'stylesheet' => null,
    'url_target' => null,
);

$mapConfigVarMap['host'] = Array(
    'type' => null,
    'object_id' => null,
    'host_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'context_menu' => null,
    'context_template' => null,
    'exclude_members' => null,
    'exclude_member_states' => null,
    'hover_menu' => null,
    'hover_delay' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_url' => null,
    'hover_childs_show' => null,
    'hover_childs_sort' => null,
    'hover_childs_order' => null,
    'hover_childs_limit' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'recognize_services' => null,
    'host_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['hostgroup'] = Array(
    'type' => null,
    'object_id' => null,
    'hostgroup_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'context_menu' => null,
    'context_template' => null,
    'exclude_members' => null,
    'exclude_member_states' => null,
    'hover_menu' => null,
    'hover_delay' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_url' => null,
    'hover_childs_show' => null,
    'hover_childs_sort' => null,
    'hover_childs_order' => null,
    'hover_childs_limit' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'recognize_services' => null,
    'hostgroup_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['service'] = Array(
    'type' => null,
    'object_id' => null,
    'host_name' => null,
    'service_description' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'service_view_type' => 'view_type',
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_label_show' => null,
    'line_label_pos_in' => null,
    'line_label_pos_out' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'gadget_url' => null,
    'gadget_type' => null,
    'gadget_scale' => null,
    'gadget_opts' => null,
    'context_menu' => null,
    'context_template' => null,
    'hover_menu' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_delay' => null,
    'hover_url' => null,
    'hover_childs_show' => null,
    'hover_childs_sort' => null,
    'hover_childs_order' => null,
    'hover_childs_limit' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'service_url' => 'url',
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['servicegroup'] = Array(
    'type' => null,
    'object_id' => null,
    'servicegroup_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'backend_id' => null,
    'view_type' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'context_menu' => null,
    'context_template' => null,
    'exclude_members' => null,
    'exclude_member_states' => null,
    'hover_menu' => null,
    'hover_delay' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_url' => null,
    'hover_childs_show' => null,
    'hover_childs_sort' => null,
    'hover_childs_order' => null,
    'hover_childs_limit' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'servicegroup_url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['map'] = Array(
    'type' => null,
    'object_id' => null,
    'map_name' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'view_type' => null,
    'iconset' => null,
    'line_type' => null,
    'line_arrow' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_weather_colors' => null,
    'context_menu' => null,
    'context_template' => null,
    'exclude_members' => null,
    'exclude_member_states' => null,
    'hover_menu' => null,
    'hover_template' => null,
    'hover_timeout' => null,
    'hover_delay' => null,
    'hover_url' => null,
    'hover_childs_show' => null,
    'hover_childs_sort' => null,
    'hover_childs_order' => null,
    'hover_childs_limit' => null,
    'label_show' => null,
    'label_text' => null,
    'label_x' => null,
    'label_y' => null,
    'label_width' => null,
    'label_background' => null,
    'label_border' => null,
    'label_style' => null,
    'only_hard_states' => null,
    'map_url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['line'] = Array(
    'type' => null,
    'object_id' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'line_type' => null,
    'line_cut' => null,
    'line_width' => null,
    'line_color' => null,
    'line_color_border' => null,
    'hover_menu' => null,
    'hover_template' => null,
    'hover_url' => null,
    'hover_delay' => null,
    'url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['textbox'] = Array(
    'type' => null,
    'object_id' => null,
    'text' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'background_color' => null,
    'border_color' => null,
    'style' => null,
    'use' => null,
    'h' => null,
    'w' => null,
);

$mapConfigVarMap['shape'] = Array(
    'type' => null,
    'object_id' => null,
    'icon' => null,
    'x' => null,
    'y' => null,
    'z' => null,
    'enable_refresh' => null,
    'hover_menu' => null,
    'hover_template' => null,
    'hover_url' => null,
    'hover_delay' => null,
    'url' => null,
    'url_target' => null,
    'use' => null,
);

$mapConfigVarMap['template'] = Array(
    'type' => null,
    'name' => null,
    'object_id' => null,
);

?>
