<?php

namespace JjaDev\QuoteInvoiceBundle\Service;

use JjaDev\QuoteInvoiceBundle\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class ClientService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createClient(Client $client): Client
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    public function updateClient(Client $client): Client
    {
        $client->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $client;
    }

    public function deleteClient(Client $client): void
    {
        // Check if client has quotes or invoices
        if ($client->getQuotes()->count() > 0 || $client->getInvoices()->count() > 0) {
            // Instead of deleting, mark as inactive
            $client->setIsActive(false);
            $client->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        } else {
            $this->entityManager->remove($client);
            $this->entityManager->flush();
        }
    }

    public function archiveClient(Client $client): Client
    {
        $client->setIsActive(false);
        $client->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $client;
    }

    public function restoreClient(Client $client): Client
    {
        $client->setIsActive(true);
        $client->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $client;
    }

    public function findActiveClients(): array
    {
        return $this->entityManager->getRepository(Client::class)
            ->findBy(['isActive' => true], ['name' => 'ASC']);
    }

    public function searchClients(string $searchTerm): array
    {
        return $this->entityManager->getRepository(Client::class)
            ->findBySearchTerm($searchTerm);
    }

    public function getClientStats(Client $client): array
    {
        return $this->entityManager->getRepository(Client::class)
            ->getClientStats($client);
    }

    public function exportClientsToArray(array $clients): array
    {
        $data = [];
        foreach ($clients as $client) {
            $data[] = [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'company_name' => $client->getCompanyName(),
                'email' => $client->getEmail(),
                'phone' => $client->getPhone(),
                'address' => $client->getAddress(),
                'postal_code' => $client->getPostalCode(),
                'city' => $client->getCity(),
                'country' => $client->getCountry(),
                'vat_number' => $client->getVatNumber(),
                'is_active' => $client->isActive(),
                'created_at' => $client->getCreatedAt()->format('Y-m-d H:i:s'),
                'quotes_count' => $client->getQuotes()->count(),
                'invoices_count' => $client->getInvoices()->count(),
            ];
        }
        return $data;
    }
}