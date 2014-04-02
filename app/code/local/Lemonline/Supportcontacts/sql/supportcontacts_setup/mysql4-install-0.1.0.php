<?php
$installer = $this;
$installer->startSetup();

$installer->run("
INSERT INTO {$this->getTable('core_email_template')} (`template_code`, `template_text`, `template_type`, `template_subject`, `template_sender_name`, `template_sender_email`, `added_at`, `modified_at`) VALUES
('Support Contact Form', 'Name: {{var data.name}}\r\nSubject: {{var data.subject}}\r\nE-mail: {{var data.email}}\r\nTelephone: {{var data.telephone}}\r\nWebsite url: {{var data.website_url}}\r\nMagento version: {{var data.magento_version}}\r\nMagento module: {{var data.magento_module}}\r\nDescription: {{var data.description}}', 1, 'Support Contact Form', NULL, NULL, NOW(), NOW());
");
$installer->endSetup();
