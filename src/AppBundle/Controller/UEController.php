<?php

namespace AppBundle\Controller;

use AppBundle\Entity\UE;
use AppBundle\Form\ListUEType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class UEController
 * Fonctionnalités du super administrateur liées aux UEs
 *
 * @package AppBundle\Controller
 *
 * @Route("/admin/ue")
 */
class UEController extends Controller
{
    /**
     * Affichage de la liste de toutes les UEs
     *
     * @Route("/", name="ue_index")
     * @Method("GET")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //Récupération de toutes les promotions
        $em = $this->getDoctrine()->getManager();
        $uEs = $em->getRepository('AppBundle:UE')->findAll();

        //Affichage
        return $this->render('AppBundle:Ue:index.html.twig', array(
            'uEs' => $uEs,
        ));
    }

    /**
     * Création d'une nouvelle UE
     *
     * @Route("/new", name="ue_new")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        //Création de l'UE et du formulaire associé
        $uE = new Ue();
        $form = $this->createForm('AppBundle\Form\UEType', $uE);

        //Récupération de la requête et vérifie si le formulaire est envoyé
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Sauvegarde de l'UE
            $em = $this->getDoctrine()->getManager();
            $em->persist($uE);
            $em->flush();

            //Redirection vers la fiche de l'UE
            $this->addFlash('success', "L'UE a bien été ajoutée !");
            return $this->redirectToRoute('ue_show', array('id' => $uE->getId()));
        }

        //Affichage du formulaire
        return $this->render('AppBundle:Ue:new.html.twig', array(
            'uE' => $uE,
            'form' => $form->createView(),
        ));
    }


    /**
     * Upload d'un fichier CSV contenant une liste d'UEs
     *
     * @Route("/uploadUEList", name="user_upload_ue_list")
     * @Method({"GET", "POST"})
     */
    public function uploadUEListAction(Request $request)
    {
        //TODO: verifier l'unicité du code de l'ue
        //Création du formulaire et vérifie qu'il est correctement envoyé
        $form = $this->createForm(ListUEType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() & $form->isValid()) {
            //Récupération du fichier
            $file = $form->getData()['listuecsv'];

            //Sauvegarde temporaire du fichier
            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $path = __DIR__ . '/../../../web/upload';
            $file->move($path, $filename);

            //Analyse du fichier
            $CSVToArray = $this->get('app.csvtoarray');
            $data = $CSVToArray->convert($path . "/" . $filename, ',', array(
                'code',
                'name',
            ));

            //Suppression du fichier après analyse
            unlink($path . "/" . $filename);
            $em = $this->getDoctrine()->getManager();

            //Création des UEs
            foreach($data as $ueData) {
                $ue = new UE();
                $ue->setCode($ueData['code']);
                $ue->setName(utf8_encode($ueData['name']));
                $em->persist($ue);
            }
            //Sauvegarde
            $em->flush();

            //Redirection avec un message de succès
            $this->addFlash('success', count($data)." UE(s) ont été ajoutées à la base de données.");
            return $this->redirectToRoute('ue_index');
        }

        //Affichage du formulaire
        return $this->render('AppBundle:Ue:uploadUeList.html.twig', array(
            'form' => $form->createView()
        ));

    }

    /**
     * Affiche la fiche d'une UE si elle est trouvée
     *
     * @Route("/{id}", name="ue_show")
     * @Method("GET")
     *
     * @param UE $uE UE à afficher
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(UE $uE)
    {
        //Création du formulaire de suppression pour le proposer sur l'affichage
        $deleteForm = $this->createDeleteForm($uE);

        //Affichage de la fiche
        return $this->render('AppBundle:ue:show.html.twig', array(
            'uE' => $uE,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Affichage d'un formulaire permettant l'édition d'une UE choisie
     *
     * @Route("/{id}/edit", name="ue_edit")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param UE $uE UE à éditer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, UE $uE)
    {
        //Création du formulaire d'édition de la promotion
        $editForm = $this->createForm('AppBundle\Form\UEType', $uE);

        //Récupération de la requête et vérifie si le formulaire est envoyé
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //Sauvegarde dans la base de données
            $this->getDoctrine()->getManager()->flush();

            //Redirection vers la fiche de la promotion
            $this->addFlash('success', "L'UE a bien été éditée !");
            return $this->redirectToRoute("ue_show", array('id' => $uE->getId()));
        }

        //Affichage du formulaire
        return $this->render('AppBundle:Ue:edit.html.twig', array(
            'uE' => $uE,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Supprime l'UE passée en paramètre
     *
     * @Route("/{id}", name="ue_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param UE $uE UE à supprimer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, UE $uE)
    {
        //Création du formulaire et récupération de la requête
        $form = $this->createDeleteForm($uE);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Suppresion de la promotion
            $em = $this->getDoctrine()->getManager();
            $em->remove($uE);
            $em->flush();
        }

        //Redirection vers la liste des promotions avec un message de succès
        $this->addFlash('success', "L'UE a bien été supprimée !");
        return $this->redirectToRoute('ue_index');
    }

    /**
     * Créé le formmulaire de suppression d'une promotion
     *
     * @param UE $uE UE à laquelle on créé le formulaire
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(UE $uE)
    {
        //Création et renvoi du formulaire
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ue_delete', array('id' => $uE->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
