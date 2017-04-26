<?php
    class VerifyCode
    {
        // 验证码
        private $image;                                 // 图片句柄
        private $image_width = 120;                     // 图片宽度
        private $image_height = 35;                     // 图片高度
        private $image_color = array(255, 0, 0);          // 图片颜色
        private $font_size = array(20, 25);             // 字体大小
        private $verify_code = "";                      // 验证码
        
        private $code_length = 4;                       // 验证码长度
        private $point_count = 50;                      // 干扰点个数
        private $line_count = 3;                        // 线条干扰数量
        
        private $error_num = 0;
        private $error_message;
        
        
       
        
        public function __construct($options = array())
        {
            //判断服务器环境是否安装了GD库
            if (!extension_loaded('gd')) {
                if (!dl('gd.so')) {
                    $this->error_num = 1;
                }
            }
            
            // 获取类内默认成员
            $menber = get_class_vars(get_class($this));            
            // 遍历赋值所需选项
            foreach ($options as $key => $value)
            {              
                if (in_array($key, $menber))
                {
                    $this->$key = $value;
                }
            }
        }
        
        /**
         * 核心方法 显示图片
         * @param string $font_file
         */
        public function showImage($font_file = "")
        {
            // 创建图片
            if (!$this->createImage())
            {
                return false;
            }
            
            // 设置干扰元素
            $this->setDisturb();
            
            // 生成验证码
            $this->createVerifyCode($font_file);

            // 输出图像
            if(!$this->ouputImage())
            {
                return false;
            }
            
            return true;
        }
        
        /**
         * 得到验证码
         */
        public function getVerifyCode()
        {
            return  strtolower($this->verifycode_code);
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
         * 创建图片
         */
        private function createImage()
        {
            // 创建图片
            $this->image = imagecreatetruecolor($this->image_width, $this->image_height);
            
            if (!$this->image)
            {
                return false;
            }
            
            // 设置一个颜色
            $color = imagecolorallocate($this->image, $this->image_color[0], $this->image_color[1], $this->image_color[2]);
            // 填充颜色
            imagefilledrectangle($this->image, 0, 0, $this->image_width, $this->image_height, $color);
            
            return true;
        }
        
        /**
         * 得到一个随机颜色
         * @return number
         */
        private function getRandColor()
        {
            return imagecolorallocate($this->image, mt_rand(0, 255),  mt_rand(0, 255),  mt_rand(0, 255));
        }
        
        /**
         * 设置干扰元素
         */
        private function setDisturb()
        {
            // 设置干扰点
            for ($i = 0; $i < $this->point_count; $i++)
            {
                imagesetpixel($this->image, mt_rand(0, $this->image_width), mt_rand(0, $this->image_height), $this->getRandColor());
            }
            
            // 设置干扰线
            for ($i = 0; $i < $this->line_count; $i++)
            {
                imageline($this->image, mt_rand(0, $this->image_width), mt_rand(0, $this->image_height), mt_rand(0, $this->image_width), mt_rand(0, $this->image_height), $this->getRandColor());
            }
        }
        
        /**
         * 创建验证码
         * @param unknown $font_file
         */
        private function createVerifyCode($font_file)
        {
            // 得到所有数字和字母
            $str =  implode("", array_merge(range(0, 9), range('a', 'z'), range('A', 'Z')));
            
            
            // 得到length位随机码
            for ($i = 0; $i < $this->code_length; $i++)
            {
                // 字体大小
                $font_size = mt_rand($this->font_size[0], $this->font_size[1]);
                // 字体角度
                $rangle = mt_rand(-15, 15);
                // 获取字体的大小
                $fong_width = imagefontwidth($font_size);
                $font_height = imagefontheight($font_size);
                // 两个各留20px
                $x = 20;
                $x_add = (($this->image_width-40)/$this->code_length);
                // 随机打乱字符串
                $rand_char = str_shuffle($str);
                
                // 查看文字文件是否存在
                if ($font_file != "" && file_exists($font_file))
                {
                    // 填充的位置 在字体的左下方算起
                    $y = ($this->image_height + $font_height)/2;
                    // 填充文字
                    imagettftext($this->image, $font_size, $rangle, $x+$i*$x_add, $y, $this->getRandColor(), $font_file, $rand_char{0});
                }
                else 
                {
                    // 填充的位置 在字体的左上方算起
                    $y = ($this->image_height - $font_height)/2;
                    imagechar($this->image, $font_size, $x+$i*$x_add, $y, $rand_char{0}, $this->getRandColor());
                }
                
                // 验证码
                $this->verify_code += $rand_char{0};
            }
            
        }
        
        /**
         * 输出图像
         */
        private function ouputImage()
        {
            if (imagetypes() & IMG_PNG)
            {
                header("Content-Type:image/png");
                imagepng($this->image);
            }
            else
            {
                $this->error_num = 2;
                return false;
            }
            
            return true;
        }
        
        /**
         * 设置错误
         */
        private function setErrorMessage()
        {
            switch ($this->error_num)
            {
                case 0:
                    $this->error_message = "没有错误";
                    break;
                case 1:
                    $this->error_message = "没有加载GD库";    
                    break;
                case 2:
                    $this->error_message = "PHP不支持创建图片";  
                    break;     
            }
        }
        
        public function __destruct()
        {
            imagedestroy($this->image);
        }
    }

    /********************test************************************
     <?php
        session_start();
        require 'verifycode.class.php';
        
    //     $image = new VerifyCode(array(
    //      "image_width" => 200,
    //      "image_height" => 60,
    //      "image_color" => array(0, 0, 0),
    //      "code_length" => 4,
    //      "point_count" => 50,
    //      "line_count" => 4
    //     ));
        
        $image = new VerifyCode();
        
        if ($image->showImage("fonts/Inconsolata.otf"))
        {
            // 得到验证码 $_SESSION["verifycode"]
            $_SESSION["verifycode"] = $image->getVerifyCode();
        }
        else
        {
            // 如果出错 这里调试
            // $image->getErrorMessage();
        }
    */