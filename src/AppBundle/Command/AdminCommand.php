<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use UserBundle\Entity\User;

class AdminCommand extends Command {

    private $defaultPassword = "M421parD0-";

    protected function configure()
    {
        $this
            ->setName('app:admin:init-super-admin')
            ->setDescription("Créé l'utilisateur super administrateur par défaut.\nMot de passe par défaut : ".$this->defaultPassword)
            ->setHelp("Créé l'utilisateur super administrateur par défaut.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '==========================',
            "Creation de l'utilisateur",
            '==========================',
            '',
        ]);

        $helper = $this->getHelper('question');
        $questionUsername = new Question("Nom d'utilisateur [admin]: ", 'admin');
        $questionEmail = new Question("Email [null]: ", null);

        $username = $helper->ask($input, $output, $questionUsername);

        do {
            $email = $helper->ask($input, $output, $questionEmail);
        } while($email == null);

        $output->writeln([
            '',
            "Nom d'utilisateur choisit : ".$username,
            "Email choisie : ".$email,
        ]);

        $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $userRepository = $em->getRepository('UserBundle:User');
        $found = $userRepository->findOneByRole('ROLE_SUPER_ADMIN');

        if($found != null) {
            $output->writeln([
                '',
                '==========================',
                " Suppression de l'ancien",
                '==========================',
            ]);
            $em->remove($found);
            $em->flush();
        }

        $userManager = $this->getApplication()->getKernel()->getContainer()->get('fos_user.user_manager');

        $superAdmin = $userManager->createUser();
        $superAdmin->setEnabled(true);
        $superAdmin->setEmail($email);
        $superAdmin->setFirstname("admin");
        $superAdmin->setLastname("admin");
        $superAdmin->addRole('ROLE_SUPER_ADMIN');
        $superAdmin->setNumEtu(null);
        $superAdmin->setUsername($username);
        $superAdmin->setPlainPassword($this->defaultPassword);
        $superAdmin->setPublicKey("-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrlEeKGh3GpchFha7me9aEc7gGcw3zj9eT5TUE
cLAx0E/nJyj92l/nUjh9UsdJEDRJAKYeD9tiT6/SFZ7V/L8nF0Q0OCINGhQNr6lTjZK4DfzaGd+s
skWEiMu8q7gEEgYgmwzU4LQwsgIpBs0MfBxvswSN1pJRxwXhcjZR2rBwvwIDAQAB
-----END PUBLIC KEY-----");
        $superAdmin->setPrivateKey("pBchSJKPHcb5CQwaJvzKuuW1em0QHXQInIuLDVVNruMI8GewradcZ_gHYtlwjixeZSSwhiPMAxHVICTZMooeN8HqprxTUJGsW7AJUAW3wSnMdsg3zUGbnrqn93CP6vpfz_Szw6HFzc-Pis-HM0O3M7rpCrm5P-5YZHKhuvMTwrqelxnV4rua98NDg7XoUp1tVOkwgVbdx-r-xGmJi5k9pXKXuuvVuTXF3NzuHgkSQQhUHnR3Lrnh6VIodLUIiHFiEgw2cg4UDCaTZrmNDBCGrUaC2xmvuCxnbvx_X_JdFFLCEI-d2Fswq7oLiitPm7ifw3HhnyfkUQyZ-22w9OiMbmfivYRgQzcj0SLEM12phk9tNoPZdxaCWaRtbw0ps_BrGdYxBiq31oeaSbNB0oMW1EZScbiWjofoTknIrZ9GYip0LAHTkM5Z6wJOxxr45Cwp4xO7OY_55XkG5yOf0xnc6QBtOPE9ZgK1rPo-W-LEvgLUGeHGA4B0mQSGakFzf5id0wT1hzRaeIgLyBUI08YF_bl_QgHFQ7K7042lqcrlt_ibr_OwpaLEE0vZa2FH8dcUGW_CfgS80V31LHC6RPLmGYO2NOx30ZOIpTJbXbtmnaWtZI-b58FkAnPzR_TlSBQNGMB6cpxLgub_pwGAcIY6afoZtYPUZn0Qza2TSEJXPJuMg70-j_FjeB41KdWGNDGtOI078OxreA1vYHHtIx18nxR7LdeTWynTlR0Iq-hRpg1TNjYfdHreQc6iJ_X-GlRwPFYhH-SEjKjA4843qe0HHdluTc2jXzIGF3HkVtFgBM__vRJc9_gNUb-KS2Bb1ikDTEfokm5JhC9MMmAYAFVhirpxeNJuoo09LIrzzV3yfEckLi0qalLZ2i5dm0hE8WzmD-MGX0LexI5T3cabmLMlZ8_zCc8dIzJG28_aiyqbTyjM1j1W4pp79Z1Z8HCzoVajdeYJDJ0jdNn4Lw7FKjLDoLupE9M92qE575P_KpwMIxRHTgIQM5ezZVEwh-fppJFGKX1vHST4BVUrpB9kjQLShXhvp4iNNA4WXpBxs_jBlC1NPN1z9GzXGaVvHjHh3sAHwJ60yrBe6jyiot6DDT_9UsPu5-iEx1Vu2n-7fz47jdxhtyCqeYd2KxqmOeHao_RuwvV964cvPJ_ZECGROI7P1xLUputZqrhp8ZR-Dp8LZxvwhOnBXxAlC_7E7yFGlJKiKn2ql_lSvJKAqfcUnvb5cw,,");

        $userManager->updateUser($superAdmin);

        $output->writeln([
            '',
            '==========================',
            "   Creation terminee !!",
            '==========================',
            '',
        ]);
    }

}