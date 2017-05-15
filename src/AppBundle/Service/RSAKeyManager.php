<?php

namespace AppBundle\Service;

use AppBundle\Entity\Grade;
use UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Sinner\Phpseclib\Crypt\Crypt_RSA;

/**
 * Service de gestion des clés RSA et des principales opérations de cryptage
 *
 * @package AppBundle\Service
 */
class RSAKeyManager
{
    /**
     * @var EntityManager|null Entity manager
     */
    private $em = null;

    /**
     * RSAKeyManager constructor.
     * @param EntityManager $em Entity manager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Fonction qui génère et sauvegarde les clés RSA d'un utilisateur
     *
     * @param User $user Utilisateur à qui générer la clé
     * @return bool Succès ou non
     */
    public function generateUserKeys(User $user) {
        //Récupération du plugin de cryptage RSA et récupération du repository Utilisateur
        $crypt_rsa = new Crypt_RSA();
        $userRepository = $this->em->getRepository('UserBundle:User');

        //Génération des clé en vérifiant qu'elle n'existe pas déjà en base de données
        do {
            $keys = $crypt_rsa->createKey();

            //Recherche si une clef existe déjà
            $found = $userRepository->findBy(array(
                'publicKey' => $keys['publickey'],
            ));
        } while ($found != null);

       //Création du token qui va permettre de créer le lien d'activation du compte (vérifie qu'il est unique)
        do{
            $activationToken = bin2hex(random_bytes(16));
            $existToken = $userRepository->findOneBy(array(
                'activationToken' => $activationToken,
            ));

        }while($existToken != null);
        $user->setActivationToken($activationToken);

        //Sauvegarde des clés et cryptage de la clé privée avec un chiffrement par mot de passe (uniquement si le compte est activé)
        $user->setPublicKey($keys['publickey']);
        if($user->isEnabled())
            $user->setPrivateKey($this->cryptByPassword($keys['privatekey'], $user->getPlainPassword()));
        else
            $user->setPrivateKey($keys['privatekey']);

        //Recherche du super admin dans la base de données
        $superAdmin = $userRepository->findOneByRole('ROLE_SUPER_ADMIN');

        //TODO : vérifier a chaque utilisation le retour de la fonction
        //Le super admin est trouvé
        if($superAdmin != null) {
            //Sauvegarde de la clé privé chiffré avec la clé publique du super admin afin de pouvoir modifier son mot de passe en cas d'oubli
            $crypt_rsa->loadKey($superAdmin->getPublicKey());
            $privateKeyAdmin = utf8_encode($crypt_rsa->encrypt($keys['privatekey']));
            $user->setPrivateKeyAdmin($privateKeyAdmin);
            return true;
        }
        //Echec de la recherche
        else {
            return false;
        }
    }

    /**
     * Fonction qui chiffre une donnée avec un mot de passe
     *
     * @param $data string Donnée à chiffrer
     * @param $password string Mot de passe
     * @return string Donnée chiffrée
     */
    public function cryptByPassword($data, $password)
    {
        return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($password), serialize($data), MCRYPT_MODE_CBC, md5(md5($password)))), '+/=', '-_,');
    }

    /**
     * Fonction qui déchiffre une donnée à partir d'un mot de passe
     *
     * @param $data string Donnée à chiffrer
     * @param $password string Mot de passe
     * @return mixed|null Donnée chiffrée si déchiffrement réussi sinon null
     */
    public function decryptByPassword($data, $password)
    {
        $result = @unserialize(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($password), base64_decode(strtr($data, '-_,', '+/=')), MCRYPT_MODE_CBC, md5(md5($password))), "\0"));
        if($result !== false) {
            return $result;
        }

        return null;
    }

    /**
     * Fonction qui récupère la clé privé non chiffré d'un utilisateur avec son mot de passe
     *
     * @param User $user Utilisateur à qui on récupère la clé
     * @param $password string Mot de passe
     * @return mixed|null Clé privée déchiffrée si réussi, sinon null
     */
    public function getUserPrivateKey(User $user, $password) {
        $key = $this->decryptByPassword($user->getPrivateKey(), $password);
        return $key;
    }

    /**
     * Fonction qui décrypte la note d'un élève et vérifie la signature
     *
     * @param $studentPrivateKey string Clé privée de l'étudiant
     * @param Grade $grade Note de l'étudiant à déchiffrer
     * @return string Note déchiffrée
     */
    public function decryptGradeStudent($studentPrivateKey, Grade $grade) {
        //Récupération du plugin de cryptage RSA
        $rsa = new Crypt_RSA();

        //Déchiffrement de la note et vérification de la signature
        $gradeText = utf8_decode($grade->getGrade());
        $rsa->loadKey($studentPrivateKey);
        $gradeText = $rsa->decrypt($gradeText);
        $rsa->loadKey($grade->getTeacher()->getPublicKey());
        $gradeText = $rsa->decrypt($gradeText);

        return $gradeText;
    }

    /**
     * Fonction qui décrypte la note d'un élève pour que le prof puisse la lire
     *
     * @param $teacherPrivateKey string Clé privée du professeur
     * @param Grade $grade Note de l'étudiant à déchiffrer
     * @return string Note déchiffrée
     */
    public function decryptGradeTeacher($teacherPrivateKey, Grade $grade) {
        //Récupération du plugin de cryptage RSA
        $rsa = new Crypt_RSA();

        //Déchiffrement de la note
        $gradeText = utf8_decode($grade->getGradeTeacher());
        $rsa->loadKey($teacherPrivateKey);
        $gradeText = $rsa->decrypt($gradeText);

        return $gradeText;
    }

    /**
     * Fonction qui crypte une note pour un élève et un professeur donnée
     * Renvoi la note crypté pour l'étudiant ainsi que la note pour le professeur
     *
     * @param User $student Etudiant à qui on crypte la note
     * @param $publicKey string Clé publique du professeur
     * @param $privateKey string Clé privée du professeur
     * @param $gradeText string Note sous forme de texte à crypter
     *
     * @return array Tableau contenant la note chiffrée pour l'étudiant et le professeur
     */
    public function cryptStudentGrade(User $student, $publicKey, $privateKey, $gradeText) {
        //Récupération du plugin de cryptage RSA
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

        //Renvoi des deux notes cryptées
        return array(
            'grade' => $grade,
            'gradeTeacher' => $gradeTeacher,
        );
    }
}