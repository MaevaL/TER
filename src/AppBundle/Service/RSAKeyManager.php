<?php

namespace AppBundle\Service;

class RSAKeyManager
{
    public function cryptByPassword($data, $password)
    {
        return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($password), serialize($data), MCRYPT_MODE_CBC, md5(md5($password)))), '+/=', '-_,');

    }

    public function decryptByPassword($data, $password)
    {
        $result = @unserialize(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($password), base64_decode(strtr($data, '-_,', '+/=')), MCRYPT_MODE_CBC, md5(md5($password))), "\0"));
        if($result !== false) {
            return $result;
        }

        return null;
    }
}