<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Flashcards;
use App\Form\FlashcardSearchType;
use App\Form\FlashcardsType;
use App\Repository\FlashcardsRepository;
use App\Service\OpenAiService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
class FlashcardsController extends AbstractController
{
    public function __construct(
        private FlashcardsRepository $flashcardsRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ){
    }
    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('/flashcards/home.html.twig');
    }

    #[Route('/api/flashcards', name: "cards_all", methods: ['GET'])]
    public function getCard(Request $request): Response
    {
        $user = $this->security->getUser();
        if(!is_object($user) || !$user instanceof User){
            throw new \Exception('The user object is not instance of User class');
        }
        $userId = $user->getId();
        $queryBuilder = $this->flashcardsRepository->createNoneQueryBuilder($userId);
        $adapter = new QueryAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $currentPage = $request->query->getInt('page', 1);
        $pagerfanta->setMaxPerPage(3);
        $pagerfanta->setCurrentPage($currentPage);
        return $this->render('flashcards/flashcards.html.twig', [
            'flashcards' => $pagerfanta->getCurrentPageResults(),
            'pager' => $pagerfanta,
        ]);
    }

    #[Route('/api/flashcards/search', name: "search_card")]
    public function search(Request $request): Response
    {
        $form = $this->createForm(FlashcardSearchType::class);
        $form->handleRequest($request);
        dump($form->createView());
        $results = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $searchQuery = $form->get('query')->getData();
            $results = $this->entityManager->getRepository(Flashcards::class)
                ->createQueryBuilder('f')
                ->where('f.topic LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%')
                ->getQuery()
                ->getResult();
            if (count($results) > 0) {
                return $this->redirectToRoute('cards_find', ['id' => $results[0]->getId()]);
            }
        }
        return $this->render('flashcards/search.html.twig',[
            'form' => $form->createView(),
            'results' => $results,
        ]);
    }
    #[Route('/api/flashcards/add', name: "cards_add", methods: ['GET','POST'])]
    public function add(Request $request): Response
    {
        $flashcard = new Flashcards();
        $user = $this->security->getUser();
        if(!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(FlashcardsType::class, $flashcard);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $flashcard = $form->getData();
            $flashcard->setUser($user);
            $this->entityManager->persist($flashcard);
            $this->entityManager->flush();
            return $this->redirectToRoute('cards_all');
        }
        return $this->render('flashcards/new.html.twig',[
            'form' => $form,
        ]);
    }

    #[Route('/api/flashcards/{id}', name:"cards_find")]
    public function findById($id): Response
    {
        $card = $this->flashcardsRepository->find($id);
        return $this->render('flashcards/foundcard.html.twig',[
            'id' => $id,
            'flashcard' => $card,
        ]);
    }

    #[Route('/api/flashcards/{id}/delete', name:"cards_delete", methods: ['POST'])]
    public function delete($id): Response
    {
        $card = $this->flashcardsRepository->find($id);
        if(!$card){
            throw $this->createNotFoundException('Flashcard not found');
        }
        $this->entityManager->remove($card);
        $this->entityManager->flush();
        $this->addFlash('notice', 'Card deleted successfully!');
        if($card->getCardStatus() == 'Done'){
            return $this->redirectToRoute('box_done');
        }
        return $this->redirectToRoute('cards_all');
    }

    #[Route('/api/flashcards/{id}/update', name:"cards_update")]
    public function update($id,Request $request): Response
    {
        $card = $this->flashcardsRepository->find($id);
        $form = $this->createFormBuilder($card)
            ->add('topic', TextType::class)
            ->add('answer', TextType::class)
            ->add('save', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->entityManager->flush();
            $this->addFlash('success', 'Card updated successfully!');
            return $this->redirectToRoute('cards_all');
        }
        return $this->render('flashcards/cardupdate.html.twig',[
            'form' => $form,
        ]);
    }
}