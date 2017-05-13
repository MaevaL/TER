<?php

namespace AppBundle\Service;

use AppBundle\Entity\Grade;
use Doctrine\ORM\EntityManager;
use Sinner\Phpseclib\Crypt\Crypt_RSA;
use UserBundle\Entity\User;

class RSAKeyManager
{
    private $em = null;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /*
     * Fonction qui génère et sauvegarde les clés RSA d'un utilisateur
     */
    public function generateUserKeys(User $user) {
        $crypt_rsa = new Crypt_RSA();
        $userRepository = $this->em->getRepository('UserBundle:User');

        do {
            $keys = $crypt_rsa->createKey();

            //Recherche si une clef existe déjà
            $found = $userRepository->findBy(array(
                'publicKey' => $keys['publickey'],
            ));
        } while ($found != null);

       //setActivationToken
        do{
            $activationToken = bin2hex(random_bytes(16));
            $existToken = $userRepository->findOneBy(array(
                'activationToken' => $activationToken,
            ));

        }while($existToken != null);
        $user->setActivationToken($activationToken);

        $user->setPublicKey($keys['publickey']);

        if($user->isEnabled())
            $user->setPrivateKey($this->cryptByPassword($keys['privatekey'], $user->getPlainPassword()));
        else
            $user->setPrivateKey($keys['privatekey']);

        $superAdmin = $userRepository->findOneByRole('ROLE_SUPER_ADMIN');

        //TODO : vérifier a chaque utilisation le retour de la fonction
        if($superAdmin != null) {
            $crypt_rsa->loadKey($superAdmin->getPublicKey());
            $privateKeyAdmin = utf8_encode($crypt_rsa->encrypt($keys['privatekey']));
            $user->setPrivateKeyAdmin($privateKeyAdmin);
            return true;
        } else {
            return false;
        }
    }

    /*
     * Fonction qui chiffre une donnée avec un mot de passe
     */
    public function cryptByPassword($data, $password)
    {
        return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($password), serialize($data), MCRYPT_MODE_CBC, md5(md5($password)))), '+/=', '-_,');

    }

    /*
     * Fonction qui déchiffre une donnée à partir d'un mot de passe
     */
    public function decryptByPassword($data, $password)
    {
        $result = @unserialize(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($password), base64_decode(strtr($data, '-_,', '+/=')), MCRYPT_MODE_CBC, md5(md5($password))), "\0"));
        if($result !== false) {
            return $result;
        }

        return null;
    }

    /*
     * Fonction qui récupère la clé privé non chiffré d'un utilisateur avec son mot de passe
     */
    public function getUserPrivateKey(User $user, $password) {
        $key = $this->decryptByPassword($user->getPrivateKey(), $password);

        return $key;
    }

    /*
     * Fonction qui décrypte la note d'un élève et vérifie la signature
     */
    public function decryptGradeStudent($studentPrivateKey, Grade $grade) {
        $rsa = new Crypt_RSA();

        $gradeText = utf8_decode($grade->getGrade());
        $rsa->loadKey($studentPrivateKey);
        $gradeText = $rsa->decrypt($gradeText);
        $rsa->loadKey($grade->getTeacher()->getPublicKey());
        $gradeText = $rsa->decrypt($gradeText);

        return $gradeText;
    }

    /*
     * Fonction qui décrypte la note d'un élève pour que le prof puisse la lire
     */
    public function decryptGradeTeacher($teacherPrivateKey, Grade $grade) {
        $rsa = new Crypt_RSA();

        $gradeText = utf8_decode($grade->getGradeTeacher());
        $rsa->loadKey($teacherPrivateKey);
        $gradeText = $rsa->decrypt($gradeText);

        return $gradeText;
    }

    /*
     * Fonction qui crypte une note pour un élève et un professeur donnée
     * Renvoi la note crypté pour l'étudiant ainsi que la note pour le professeur
     */
    public function cryptStudentGrade(User $student, $publicKey, $privateKey, $gradeText) {
        $rsa = new Crypt_RSA();

        //Cryptage pour l'étudiant avec signature du prof
        $rsa->loadKey($privateKey);
        $grade = $rsa->encrypt($gradeText);
        $rsa->loadKey($student->getPublicKey());
        $grade = $rsa->encrypt($grade);
        $grade = utf8_encode($grade);

        //Cryptage pour le professeur
        $rsa->loadKey($publicKey);
        $gradeTeacher = $rsa->encrypt($gradeText);
        $gradeTeacher = utf8_encode($gradeTeacher);

        return array(
            'grade' => $grade,
            'gradeTeacher' => $gradeTeacher,
        );
    }
}