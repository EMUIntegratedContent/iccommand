<?php

namespace App\Controller\Map;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use App\Entity\MapBathroom;
use App\Entity\MapParking;
use App\Entity\MapItem;

class MapBathroomController extends Controller
{
    /**
     * @Route("/map/bathroom", name="map_bathroom")
     */
    public function index()
    {
      $encoders = array(new XmlEncoder(), new JsonEncoder());
      $normalizers = array(new ObjectNormalizer());

      $serializer = new Serializer($normalizers, $encoders);
        //return $this->render('map_bathroom/index.html.twig', [
        //    'controller_name' => 'MapBathroomController',
        //]);

        // you can fetch the EntityManager via $this->getDoctrine()
        // or you can add an argument to your action: index(EntityManagerInterface $em)
        $em = $this->getDoctrine()->getManager();

        $bathroom = new MapBathroom();
        $bathroom->setName('Zillabong');
        $bathroom->setSlug('Zillabong');
        $bathroom->setDescription('Also Not Ergonomic and stylish!');
        $bathroom->setLatitudeIllustration(52.99999);
        $bathroom->setLongitudeIllustration(19.9949);
        $bathroom->setLatitudeSatellite(-20.495959);
        $bathroom->setLongitudeStaellite(49.94954);
        $bathroom->setIsGenderNeutral(false);

        $parking = new MapParking();
        $parking->setName('South Lot');
        $parking->setSlug('south-lot');
        $parking->setDescription('Parking woot');
        $parking->setLatitudeIllustration(-52.99999);
        $parking->setLongitudeIllustration(-19.9949);
        $parking->setLatitudeSatellite(20.495959);
        $parking->setLongitudeStaellite(-49.94954);
        $parking->setHours("Mon Tue 9-5; Wed-Sun closed");
        $parking->setSpaces(399);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        //$em->persist($bathroom);
        //$em->persist($parking);

        // actually executes the queries (i.e. the INSERT query)
        //$em->flush();

        $item = $this->getDoctrine()->getRepository(MapItem::class)->findBy(array());
        $jsonContent = $serializer->serialize($item, 'json');

        //return new Response('Saved new product with id '.$bathroom->getId());
        return new JsonResponse($jsonContent);
    }
}
