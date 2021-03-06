<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Customer;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;


class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {

        $faker = Faker\Factory::create('fr_FR');

        $company = $manager->getRepository(Company::class)
            ->findAll();
        $min = reset($company);
        $max = end($company);

        $customer = array();
        for ($j = 0; $j < 20; $j++) {
            $randomCompany = $manager->getRepository(Company::class)->find(mt_rand($min->getId(), $max->getId()));
            $customer[$j] = new Customer();
            $customer[$j]->setCompany($randomCompany);
            $customer[$j]->setFirstName($faker->firstName);
            $customer[$j]->setLastName($faker->lastName);
            $customer[$j]->setAccess(false);
            $customer[$j]->setEmailAddress($faker->email);
            $customer[$j]->setCreatedAt(new DateTime);
            $customer[$j]->setUpdatedAt(new DateTime);
            $manager->persist($customer[$j]);
        }

        $customer = new Customer();
        $customer->setFirstName('John');
        $customer->setLastName('Doe');
        $customer->setAccess(true);
        $customer->setEmailAddress('john@doe.com');
        $customer->setCreatedAt(new DateTime);
        $customer->setUpdatedAt(new DateTime);
        $manager->persist($customer);

        $manager->flush();
    }
}
