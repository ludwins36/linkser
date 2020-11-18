<?php 

namespace Vexsoluciones\Linkser\Model\Config\Backend;


class File extends \Magento\Config\Model\Config\Backend\File
{
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();
        if (!empty($file)) {
            
            
            /*** Here you can write your custom code to save data in custom table ***/
            
            $target = $this->_mediaDirectory->getAbsolutePath('keys_linkser/public_linkser/');
            $uploadDir = $this->_getUploadDir(); 
            

          
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->setAllowedExtensions(['rsa']);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($target);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $result['file'];
            if ($filename) {

                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                $this->setValue($filename);
            }

        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->setValue('');
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }



}