<?php
    /**
     * 参数数组修改，索引必须相同
     * private $file_path;                                   // 上传到那个路径
     * private $allwo_type = array('jpg', 'png', 'gif');     // 允许的类型
     * private $max_size = 2097152;                          // 允许最大大小  2M
     * private $is_rand_name = true;                         // 是否随机名字
     * @author gp·s
     *
     */
    class FileUpload
    {
        // 路径名称注意  文件夹名字后面不用加 /  加了也会自动删去
        private $file_path;                                   // 上传到那个路径
        private $allwo_type = array('jpg', 'png', 'gif');     // 允许的类型
        private $max_size = 2000000;                          // 允许最大大小
        private $is_rand_name = true;                         // 是否随机名字
        private $is_allow_all = false;                        // 允许所有类型文件
        
        private $new_file_name;                               // 新文件名
        
        private $error_num = 0;                               // 错误号
        private $error_message;                               // 错误消息
        
        // 上传文件属性
        private $upload_file_name;
        private $upload_file_type;
        private $upload_file_tmp_name;
        private $upload_file_size;
        
        // 错误消息
        private $upload_file_error_num;
        
        
        /**
         * 根据用户输入的关联数组赋值成员
         * $arr = array(       // 参数传一个数组
         *     'allwo_type'=>array('txt', 'jpg', 'png', 'gif'),
         *     'file_path' => 'UploadFile',
         *     'is_rand_name' => true,
         *     'max_size' => '2000000'
         *  )
         * @param array $options
         */
        public function __construct($options = array())
        {
            // 赋值成员
            if (!empty($options))
            {
                // 把类所有属性装进一个数组
                $member = get_class_vars(get_class($this));
                foreach ($options as $key=>$value)
                {
                    // 全部转换为小写  防止用户大小写错写
                    $key = strtolower($key);
        
                    if (!in_array($key, $member))
                    {
                        continue;
                    }
                    // 赋值
                    $this->$key = $value;
                }
            }
            
            // 把文件名设置为最后以为没有斜杆的
            $this->file_path = rtrim($this->file_path, '/');
            
        }
        
        /**
         * 表单控件的name
         * @param string $input_name
         */
        public function uploadFile($input_name)
        {
            // 设置超全局成员并检查系统性错误
            if(!$this->setFilesMember($input_name))
            {
                return false;
            }
            
            // 检查错误 出错返回假
            if(!$this->checkError())
            {
                return false;
            }
            
            if (!$this->moveFileToDir())
            {
                return false;
            }
            
            return true;
        }
        
        
        /**
         * 得到错误消息
         * @return string
         */
        public function getErrorMessage()
        {
            $this->setErrorMessage();
            return $this->error_message;
        }
        
        /**
         * 得到错误号
         * @return number
         */
        public function getErrorNum()
        {
            return $this->error_num;
        }
        
        /**
         * 得到新文件名
         * @return string
         */
        public function getNewFileName()
        {
            return $this->new_file_name;
        }
        
        /**
         * 检查所有用户设置错误
         */
        private function checkError()
        {
            // 检查路径                                  检查文件大小                                             检查文件类型
            if ($this->checkPath() && $this->checkFileSize() && $this->checkFileType())
            {
                return true;
            }
            
            return false;
        }
        
        /**
         * 检查文件上传路径
         * @return boolean
         */
        private function checkPath()
        {
            if (empty($this->file_path))
            {
                $this->error_num = -5;  // 未指定路径
                return false;
            }
            
            // 文件夹不存在或者不可写就创建
            if (!file_exists($this->file_path) || !is_writeable($this->file_path))
            {
                if (!@mkdir($this->file_path, 0755))
                {
                    $this->error_num = -4;  // 建立缓存文件夹失败，请重新指定上传目录
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * 检查文件大小
         * @return boolean
         */
        private function checkFileSize()
        {
            if ($this->upload_file_size > $this->max_size)
            {
                $this->error_num = -2;  // 上传文件大小大于限定大小
                return false;
            }
            
            return true;
        }
        
        /**
         * 检查文件类型
         * @return boolean
         */
        private function checkFileType()
        {
            // 允许所有文件类型
            if ($this->is_allow_all)
            {
                return true;
            }

            if (!in_array($this->upload_file_type, $this->allwo_type))
            {
                $this->error_num = -1;  // 文件类型不对
                return false;
            }
            
            return true;
        }
        
        /**
         * 设置超全局数组成员
         * @param string $input_name    上传表单name
         * @return boolean
         */
        private function setFilesMember($input_name)
        {
            // 检查是否有系统性的错误
            $this->upload_file_error_num = $_FILES[$input_name]['error'];
            // 获取数组的上一次索引
            if ($this->upload_file_error_num != 0)
            {
                return false;
            }
        
            // 把超全局文件数组赋值到成员里
            $this->upload_file_name = $_FILES[$input_name]['name'];
            // 文件类型 通过后缀名取
            $type = explode('.', $_FILES[$input_name]['name']);
            $this->upload_file_type = strtolower($type[count($type) - 1]);
            $this->upload_file_tmp_name = $_FILES[$input_name]['tmp_name'];
            $this->upload_file_size = $_FILES[$input_name]['size'];
        
            // 设置新文件名
            $this->setNewFileName();
        
            return true;
        }
        
        /**
         * 移动临时文件到指定上传路径
         * @return boolean
         */
        private function moveFileToDir()
        {
            
            $new_file_path = $this->file_path . '/' . $this->new_name;
            if(!@move_uploaded_file($this->upload_file_tmp_name, $new_file_path))
            {
                $this->upload_file_error = -3;  // 移动文件失败 
                
                return false;
            }
            
            return true;
        }
        
        /**
         * 设置新文件名
         */
        private function setNewFileName()
        {
            if ($this->is_rand_name)
            {
                date_default_timezone_set('Asia/Shanghai');
                $this->new_name = date('Y_m_d-H_i_s-') . rand(10, 99) . '.' . $this->upload_file_type;
                $this->new_file_name = $this->new_name;
            }
            else
            {
                $this->new_name = $this->upload_file_name;
                $this->new_file_name = $this->new_name;
            }
        }
        
        /**
         * 得到错误消息
         */
        private function setErrorMessage()
        {
            switch ($this->error_num)
            {
                case 1:
                    $this->error_message = '上传的文件超过了 php.ini 中 upload_max_filesize选项限制的值';
                    break;
                case 2:
                    $this->error_message = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                    break;
                case 3:
                    $this->error_message = '文件只有部分被上传';
                    break;
                case 4:
                    $this->error_message = '没有文件被上传';
                    break;
                case 5:
                    $this->error_message = '找不到临时文件夹';
                    break;
                case 6:
                    $this->error_message = '找不到临时文件夹';
                    break;
                case 7:
                    $this->error_message = '文件写入失败';
                    break;
                    // 自定义错误类型
                case -1:
                    $this->error_message = '未允许的类型';
                    break;
                case -2:
                    $this->error_message = '文件上传大小超过限制的大小';
                    break;
                case -3:
                    $this->error_message = '未知错误';
                    break;
                case -4:
                    $this->error_message = '建立缓存文件夹失败，请重新指定上传目录';
                    break;
                case -5:
                    $this->error_message = '未指定上传路径';
                    break;
            }
            
        }
    }
    
    /**************************test*********************************************
    <?php
        require "class/fileupload.class.php";

        $setting = [
                    'file_path' => 'upload',
                    'is_allow_all' => true,     // 允许所有文件类型
                    'is_rand_name' => true,
                    'max_size' => '1000000000'
                   ];
        $upload = new FileUpload($setting);

        // 文件核心上传
        if (!$upload->uploadFile("file"))
        {
            // 失败获取错误
            echo $upload->getErrorMessage();
        }
        else
        {
            // 成功获取新文件名
            echo $upload->getNewFileName();
        }
     */