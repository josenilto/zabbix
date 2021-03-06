<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 */

// Visibility box javascript is already added. It should not be added in popup response.
define('CVISIBILITYBOX_JAVASCRIPT_INSERTED', 1);
define('IS_TEXTAREA_MAXLENGTH_JS_INSERTED', 1);

// create form
$form = (new CForm())
	->cleanItems()
	->setId('massupdate-form')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('action', 'popup.massupdate.host')
	->addVar('ids', $data['ids'])
	->addVar('tls_accept', HOST_ENCRYPTION_NONE)
	->addVar('update', '1')
	->addVar('location_url', $data['location_url'])
	->disablePasswordAutofill();

// create form list
$hostFormList = new CFormList('hostFormList');

$hostFormList->addRow(
	(new CVisibilityBox('visible[groups]', 'groups-div', _('Original')))
		->setLabel(_('Host groups'))
		->setAttribute('autofocus', 'autofocus'),
	(new CDiv([
		(new CRadioButtonList('mass_update_groups', ZBX_ACTION_ADD))
			->addValue(_('Add'), ZBX_ACTION_ADD)
			->addValue(_('Replace'), ZBX_ACTION_REPLACE)
			->addValue(_('Remove'), ZBX_ACTION_REMOVE)
			->setModern(true)
			->addStyle('margin-bottom: 5px;'),
		(new CMultiSelect([
			'name' => 'groups[]',
			'object_name' => 'hostGroup',
			'add_new' => (CWebUser::getType() == USER_TYPE_SUPER_ADMIN),
			'data' => [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'host_groups',
					'srcfld1' => 'groupid',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'groups_',
					'editable' => true
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	]))->setId('groups-div')
);

// append description to form list
$hostFormList->addRow(
	(new CVisibilityBox('visible[description]', 'description', _('Original')))->setLabel(_('Description')),
	(new CTextArea('description', ''))
		->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
		->setMaxlength(DB::getFieldLength('hosts', 'description'))
);

// append proxy to form list
$proxyComboBox = new CComboBox('proxy_hostid', 0);
$proxyComboBox->addItem(0, _('(no proxy)'));
foreach ($data['proxies'] as $proxie) {
	$proxyComboBox->addItem($proxie['proxyid'], $proxie['host']);
}
$hostFormList->addRow(
	(new CVisibilityBox('visible[proxy_hostid]', 'proxy_hostid', _('Original')))->setLabel(_('Monitored by proxy')),
	$proxyComboBox
);

// append status to form list
$hostFormList->addRow(
	(new CVisibilityBox('visible[status]', 'status', _('Original')))->setLabel(_('Status')),
	new CComboBox('status', HOST_STATUS_MONITORED, null, [
		HOST_STATUS_MONITORED => _('Enabled'),
		HOST_STATUS_NOT_MONITORED => _('Disabled')
	])
);

$templatesFormList = new CFormList('templatesFormList');

// append templates table to form list
$newTemplateTable = (new CTable())
	->addRow(
		(new CRadioButtonList('mass_action_tpls', ZBX_ACTION_ADD))
			->addValue(_('Link'), ZBX_ACTION_ADD)
			->addValue(_('Replace'), ZBX_ACTION_REPLACE)
			->addValue(_('Unlink'), ZBX_ACTION_REMOVE)
			->setModern(true)
	)
	->addRow([
		(new CMultiSelect([
			'name' => 'templates[]',
			'object_name' => 'templates',
			'data' => [],
			'popup' => [
				'parameters' => [
					'srctbl' => 'templates',
					'srcfld1' => 'hostid',
					'srcfld2' => 'host',
					'dstfrm' => $form->getName(),
					'dstfld1' => 'templates_'
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
	])
	->addRow([
		(new CList())
			->addClass(ZBX_STYLE_LIST_CHECK_RADIO)
			->addItem((new CCheckBox('mass_clear_tpls'))->setLabel(_('Clear when unlinking')))
	]);

$templatesFormList->addRow(
	(new CVisibilityBox('visible[templates]', 'linked-templates-div', _('Original')))->setLabel(_('Link templates')),
	(new CDiv($newTemplateTable))
		->setId('linked-templates-div')
		->addStyle('margin-top: -5px;')
);

$ipmiFormList = new CFormList('ipmiFormList');

// append ipmi to form list
$ipmiFormList->addRow(
	(new CVisibilityBox('visible[ipmi_authtype]', 'ipmi_authtype', _('Original')))
		->setLabel(_('Authentication algorithm')),
	new CComboBox('ipmi_authtype', IPMI_AUTHTYPE_DEFAULT, null, ipmiAuthTypes())
);

$ipmiFormList->addRow(
	(new CVisibilityBox('visible[ipmi_privilege]', 'ipmi_privilege', _('Original')))->setLabel(_('Privilege level')),
	new CComboBox('ipmi_privilege', IPMI_PRIVILEGE_USER, null, ipmiPrivileges())
);

$ipmiFormList->addRow(
	(new CVisibilityBox('visible[ipmi_username]', 'ipmi_username', _('Original')))->setLabel(_('Username')),
	(new CTextBox('ipmi_username', ''))
		->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
		->disableAutocomplete()
);

$ipmiFormList->addRow(
	(new CVisibilityBox('visible[ipmi_password]', 'ipmi_password', _('Original')))->setLabel(_('Password')),
	(new CTextBox('ipmi_password', ''))
		->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
		->disableAutocomplete()
);

$inventoryFormList = new CFormList('inventoryFormList');

// append inventories to form list
$inventoryFormList->addRow(
	(new CVisibilityBox('visible[inventory_mode]', 'inventory_mode_div', _('Original')))->setLabel(_('Inventory mode')),
	(new CDiv(
		(new CRadioButtonList('inventory_mode', HOST_INVENTORY_DISABLED))
			->addValue(_('Disabled'), HOST_INVENTORY_DISABLED)
			->addValue(_('Manual'), HOST_INVENTORY_MANUAL)
			->addValue(_('Automatic'), HOST_INVENTORY_AUTOMATIC)
			->setModern(true)
	))->setId('inventory_mode_div')
);

$tags_form_list = new CFormList('tagsFormList');

// append tags table to form list
$tags_form_list->addRow(
	(new CVisibilityBox('visible[tags]', 'tags-div', _('Original')))->setLabel(_('Tags')),
	(new CDiv([
		(new CRadioButtonList('mass_update_tags', ZBX_ACTION_ADD))
			->addValue(_('Add'), ZBX_ACTION_ADD)
			->addValue(_('Replace'), ZBX_ACTION_REPLACE)
			->addValue(_('Remove'), ZBX_ACTION_REMOVE)
			->setModern(true)
			->addStyle('margin-bottom: 10px;'),
		renderTagTable([['tag' => '', 'value' => '']])
			->setHeader([_('Name'), _('Value'), _('Action')])
			->setId('tags-table')
	]))->setId('tags-div')
);

$hostInventoryTable = DB::getSchema('host_inventory');
foreach ($data['inventories'] as $field => $fieldInfo) {

	if ($hostInventoryTable['fields'][$field]['type'] == DB::FIELD_TYPE_TEXT) {
		$fieldInput = (new CTextArea('host_inventory['.$field.']', ''))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH);
	}
	else {
		$fieldInput = (new CTextBox('host_inventory['.$field.']', ''))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAttribute('maxlength', $hostInventoryTable['fields'][$field]['length']);
	}

	$inventoryFormList->addRow(
		(new CVisibilityBox('visible['.$field.']', $fieldInput->getId(), _('Original')))->setLabel($fieldInfo['title']),
		$fieldInput, null, 'formrow-inventory'
	);
}

// Encryption
$encryption_form_list = new CFormList('encryption');

$encryption_table = (new CFormList('encryption'))
	->addRow(_('Connections to host'),
		(new CRadioButtonList('tls_connect', HOST_ENCRYPTION_NONE))
			->addValue(_('No encryption'), HOST_ENCRYPTION_NONE)
			->addValue(_('PSK'), HOST_ENCRYPTION_PSK)
			->addValue(_('Certificate'), HOST_ENCRYPTION_CERTIFICATE)
			->setModern(true)
			->setEnabled(true)
	)
	->addRow(_('Connections from host'),
		(new CList())
			->addClass(ZBX_STYLE_LIST_CHECK_RADIO)
			->addItem((new CCheckBox('tls_in_none'))
				->setLabel(_('No encryption'))
				->setEnabled(true)
			)
			->addItem((new CCheckBox('tls_in_psk'))
				->setLabel(_('PSK'))
				->setEnabled(true)
			)
			->addItem((new CCheckBox('tls_in_cert'))
				->setLabel(_('Certificate'))
				->setEnabled(true)
			)
	)
	->addRow(
		(new CLabel(_('PSK identity'), 'tls_psk_identity'))->setAsteriskMark(),
		(new CTextBox('tls_psk_identity', '', false, 128))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAriaRequired()
	)
	->addRow(
		(new CLabel(_('PSK'), 'tls_psk'))->setAsteriskMark(),
		(new CTextBox('tls_psk', '', false, 512))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
			->setAriaRequired()
			->disableAutocomplete()
	)
	->addRow(_('Issuer'),
		(new CTextBox('tls_issuer', '', false, 1024))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
	)
	->addRow(_x('Subject', 'encryption certificate'),
		(new CTextBox('tls_subject', '', false, 1024))
			->setAdaptiveWidth(ZBX_TEXTAREA_BIG_WIDTH)
	);

$encryption_form_list->addRow(
	(new CVisibilityBox('visible[encryption]', 'encryption_div', _('Original')))->setLabel(_('Connections')),
	(new CDiv($encryption_table))
		->setId('encryption_div')
		->addStyle('margin-top: -5px;')
);

// append tabs to form
$tabs = (new CTabView())
	->addTab('hostTab', _('Host'), $hostFormList)
	->addTab('templatesTab', _('Templates'), $templatesFormList)
	->addTab('ipmiTab', _('IPMI'), $ipmiFormList)
	->addTab('tagsTab', _('Tags'), $tags_form_list)
	->addTab('macros_tab', _('Macros'), new CPartial('massupdate.macros.tab', [
		'visible' => [],
		'macros' => [['macro' => '', 'type' => ZBX_MACRO_TYPE_TEXT, 'value' => '', 'description' => '']],
		'macros_checkbox' => [ZBX_ACTION_ADD => 0, ZBX_ACTION_REPLACE => 0, ZBX_ACTION_REMOVE => 0,
			ZBX_ACTION_REMOVE_ALL => 0
		]
	]))
	->addTab('inventoryTab', _('Inventory'), $inventoryFormList)
	->addTab('encryptionTab', _('Encryption'), $encryption_form_list)
	->setSelected(0);

$form->addItem($tabs);

$form->addItem(new CJsScript($this->readJsFile('popup.massupdate.tmpl.js.php')));
$form->addItem(new CJsScript($this->readJsFile('popup.massupdate.macros.js.php')));

$output = [
	'header' => $data['title'],
	'body' => $form->toString(),
	'buttons' => [
		[
			'title' => _('Update'),
			'class' => '',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'return submitPopup(overlay);'
		]
	]
];

$output['script_inline'] = $this->readJsFile('popup.massupdate.js.php');
$output['script_inline'] .= getPagePostJs();

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
