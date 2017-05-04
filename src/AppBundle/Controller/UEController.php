<?php

namespace AppBundle\Controller;

use AppBundle\Entity\UE;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Ue controller.
 *
 * @Route("/admin/ue")
 */
class UEController extends Controller
{
    /**
     * Lists all uE entities.
     *
     * @Route("/", name="ue_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $uEs = $em->getRepository('AppBundle:UE')->findAll();

        return $this->render('AppBundle:ue:index.html.twig', array(
            'uEs' => $uEs,
        ));
    }

    /**
     * Creates a new uE entity.
     *
     * @Route("/new", name="ue_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $uE = new Ue();
        $form = $this->createForm('AppBundle\Form\UEType', $uE);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $slugify = new Slugify();
            $uE->setSlug($slugify->slugify($uE->getName()));

            $em->persist($uE);
            $em->flush();

            $this->addFlash('success', "L'UE a bien été ajoutée !");
            return $this->redirectToRoute('ue_show', array('id' => $uE->getId()));
        }

        return $this->render('AppBundle:ue:new.html.twig', array(
            'uE' => $uE,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a uE entity.
     *
     * @Route("/{id}", name="ue_show")
     * @Method("GET")
     */
    public function showAction(UE $uE)
    {
        $deleteForm = $this->createDeleteForm($uE);

        return $this->render('AppBundle:ue:show.html.twig', array(
            'uE' => $uE,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing uE entity.
     *
     * @Route("/{id}/edit", name="ue_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, UE $uE)
    {
        $deleteForm = $this->createDeleteForm($uE);
        $editForm = $this->createForm('AppBundle\Form\UEType', $uE);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $slugify = new Slugify();
            $uE->setSlug($slugify->slugify($uE->getName()));
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "L'UE a bien été éditée !");
            return $this->redirectToRoute("ue_index");
        }

        return $this->render('AppBundle:ue:edit.html.twig', array(
            'uE' => $uE,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a uE entity.
     *
     * @Route("/{id}", name="ue_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, UE $uE)
    {
        $form = $this->createDeleteForm($uE);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($uE);
            $em->flush();
        }

        $this->addFlash('success', "L'UE a bien été supprimée !");
        return $this->redirectToRoute('ue_index');
    }

    /**
     * Creates a form to delete a uE entity.
     *
     * @param UE $uE The uE entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(UE $uE)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ue_delete', array('id' => $uE->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
