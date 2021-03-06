<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/customer")
 */
class CustomerController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/", name="customer_index", methods={"GET"})
     * @param CustomerRepository $customerRepository
     * @return Response
     *
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="customer_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $customer->setCreatedAt(new DateTime());
            $customer->setUpdatedAt(new DateTime());
            $entityManager->persist($customer);
            $entityManager->flush();
            if ($customer->getAccess() == true) {
                $this->create($customer->getFirstName(), $customer->getLastName(), $customer->getEmailAddress(), $customer);
            }

            return $this->redirectToRoute('customer_index');
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="customer_show", methods={"GET"})
     * @param Customer $customer
     * @return Response
     */
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="customer_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Customer $customer
     * @return Response
     */
    public function edit(Request $request, Customer $customer): Response
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();

            if ($customer->getAccess() === true) {
                $this->create($customer->getFirstName(), $customer->getLastName(), $customer->getEmailAddress(), $customer);
            } else if ($customer->getAccess() === false) {
                $userToDelete = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->findBy(['customer' => $customer->getId()]);

                if (!empty($userToDelete)) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($userToDelete[0]);
                    $entityManager->flush();
                }

            }


            return $this->redirectToRoute('customer_index');
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="customer_delete", methods={"DELETE"})
     * @param Request $request
     * @param Customer $customer
     * @return Response
     */
    public function delete(Request $request, Customer $customer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('customer_index');
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param Customer|null $customer
     */
    public function create(string $firstName, string $lastName, string $email, ?Customer $customer)
    {
        $manager = $this->getDoctrine()->getManager();
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, strtolower($firstName . '.' . $lastName)));
        $user->setRoles(["ROLE_USER"]);
        $user->setCustomer($customer);
        $user->setCreatedAt(new DateTime());
        $user->setUpdatedAt(new DateTime());
        $manager->persist($user);
        $manager->flush();
    }
}
