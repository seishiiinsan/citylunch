<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable('today');

        $products = [
            [
                'name' => 'Poulet rôti aux herbes',
                'description' => 'Cuisse de poulet fermier rôtie, accompagnée de pommes de terre grenaille et d\'une sauce aux herbes fraîches.',
                'price' => 1290,
                'type' => Product::TYPE_PLAT,
                'stock' => 15,
            ],
            [
                'name' => 'Risotto aux champignons',
                'description' => 'Risotto crémeux aux champignons de Paris et shiitakés, parmesan affiné 24 mois.',
                'price' => 1190,
                'type' => Product::TYPE_PLAT,
                'stock' => 12,
            ],
            [
                'name' => 'Pavé de saumon, légumes vapeur',
                'description' => 'Pavé de saumon Label Rouge, légumes de saison vapeur et sauce citronnée à l\'aneth.',
                'price' => 1390,
                'type' => Product::TYPE_PLAT,
                'stock' => 10,
            ],
            [
                'name' => 'Fondant au chocolat',
                'description' => 'Fondant au chocolat noir 70%, cœur coulant, servi avec une quenelle de crème fraîche.',
                'price' => 590,
                'type' => Product::TYPE_DESSERT,
                'stock' => 20,
            ],
            [
                'name' => 'Tarte aux fraises',
                'description' => 'Tarte sablée garnie de crème pâtissière à la vanille Bourbon et de fraises gariguette.',
                'price' => 550,
                'type' => Product::TYPE_DESSERT,
                'stock' => 18,
            ],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setPrice($data['price']);
            $product->setType($data['type']);
            $product->setAvailableDate($today);
            $product->setStock($data['stock']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
