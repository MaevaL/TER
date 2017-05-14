<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Promotion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PromotionController
 * Fonctionnalités du super administrateur liées aux promotions des étudiants
 *
 * @package AppBundle\Controller
 *
 * @Route("admin/promotion")
 */
class PromotionController extends Controller
{
    /**
     * Affichage de la liste de toutes les promotions
     *
     * @Route("/", name="promotion_index")
     * @Method("GET")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //Récupération de toutes les promotions
        $em = $this->getDoctrine()->getManager();
        $promotions = $em->getRepository('AppBundle:Promotion')->findAll();

        //Affichage
        return $this->render('AppBundle:Promotion:index.html.twig', array(
            'promotions' => $promotions,
        ));
    }

    /**
     * Création d'une nouvelle promotion
     *
     * @Route("/new", name="promotion_new")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        //Création de la promotion et du formulaire associé
        $promotion = new Promotion();
        $form = $this->createForm('AppBundle\Form\PromotionType', $promotion);

        //Récupération de la requête et vérifie si le formulaire est envoyé
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Sauvegarde de la promotion
            $em = $this->getDoctrine()->getManager();
            $em->persist($promotion);
            $em->flush();

            //Redirection vers la fiche de la promotion
            return $this->redirectToRoute('promotion_show', array('id' => $promotion->getId()));
        }

        //Affichage du formulaire
        return $this->render('AppBundle:Promotion:new.html.twig', array(
            'promotion' => $promotion,
            'form' => $form->createView(),
        ));
    }

    /**
     * Affiche la fiche d'une promotion si elle est trouvée
     *
     * @Route("/{id}", name="promotion_show")
     * @Method("GET")
     *
     * @param Promotion $promotion Promotion à afficher
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Promotion $promotion)
    {
        //Création du formulaire de suppression pour le proposer sur l'affichage
        $deleteForm = $this->createDeleteForm($promotion);

        //Affichage de la fiche
        return $this->render('AppBundle:Promotion:show.html.twig', array(
            'promotion' => $promotion,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Affichage d'un formulaire permettant l'édition d'une promotion choisie
     *
     * @Route("/{id}/edit", name="promotion_edit")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param Promotion $promotion Promotion à éditer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Promotion $promotion)
    {
        //Création du formulaire d'édition de la promotion
        $editForm = $this->createForm('AppBundle\Form\PromotionType', $promotion);

        //Récupération de la requête et vérifie si le formulaire est envoyé
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //Sauvegarde dans la base de données
            $this->getDoctrine()->getManager()->flush();

            //Redirection vers la fiche de la promotion
            return $this->redirectToRoute('promotion_show', array('id' => $promotion->getId()));
        }

        //Affichage du formulaire
        return $this->render('AppBundle:Promotion:edit.html.twig', array(
            'promotion' => $promotion,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Supprime la promotion passée en paramètre
     *
     * @Route("/{id}", name="promotion_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param Promotion $promotion Promotion à supprimer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Promotion $promotion)
    {
        //Création du formulaire et récupération de la requête
        $form = $this->createDeleteForm($promotion);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Suppresion de la promotion
            $em = $this->getDoctrine()->getManager();
            $em->remove($promotion);
            $em->flush();
        }

        //Redirection vers la liste des promotions avec un message de succès
        $this->addFlash('success', "La promotion a bien été supprimée !");
        return $this->redirectToRoute('promotion_index');
    }

    /**
     * Créé le formmulaire de suppression d'une promotion
     *
     * @param Promotion $promotion Promotion à laquelle on créé le formulaire
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Promotion $promotion)
    {
        //Création et renvoi du formulaire
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('promotion_delete', array('id' => $promotion->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
