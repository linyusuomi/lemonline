<?php

class Lemonline_Supportcontacts_IndexController extends Mage_Core_Controller_Front_Action
{

    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/supportemail/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/supportemail/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/supportemail/email_template';
    const XML_PATH_ENABLED          = 'contacts/supportcontacts/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('supportcontactform')
            ->setFormAction( Mage::getUrl('*/*/send') );

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function sendAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                /**************************************************************/
                $fileName = '';
                if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
                    try {
                        $fileName       = $_FILES['attachment']['name'];
                        $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                        $fileNamewoe    = rtrim($fileName, $fileExt);
                        $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
 
                        $uploader       = new Varien_File_Uploader('attachment');
                        $uploader->setAllowedExtensions(array('doc', 'docx','pdf','png','jpeg','jpg','pjpeg','gif','x-png'));
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                 
                        $path = Mage::getBaseDir('media') . DS . 'contacts';
                        if(!is_dir($path)){
                            mkdir($path, 0777, true);
                        }                  
                        $uploader->save($path . DS, $fileName );
 
                    } catch (Exception $e) {
                        $error = true;
                    }
                }
              
                /**************************************************************/
   
                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
 
                /**************************************************************/
                //sending file as attachment
                $attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
                if(file_exists($attachmentFilePath)){
                    $fileContents = file_get_contents($attachmentFilePath);
                    $attachment   = $mailTemplate->getMail()->createAttachment($fileContents);
                    $attachment->filename = $fileName;
                }
                /**************************************************************/
                 
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('supportcontacts')->__('Thanks for your enquiry. We will be in touch shortly!'));
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('supportcontacts')->__('Sorry, we were unable to submit your enquiry.'));
                $this->_redirect('*/*/');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
    }

}
