<?php
class OfferController {
    private $offerModel;

    public function __construct(Offer $offerModel) {
        $this->offerModel = $offerModel;
    }

    public function listOffers(): array {
        return $this->offerModel->findAll();
    }

    public function getStats(): array {
        return $this->offerModel->getStats();
    }

    public function getOffer(int $id): ?array {
        return $this->offerModel->findById($id);
    }

    public function createOffer(array $data): int {
        return $this->offerModel->create($data);
    }

    public function updateOffer(int $id, array $data): bool {
        return $this->offerModel->update($id, $data);
    }

    public function deleteOffer(int $id): bool {
        return $this->offerModel->delete($id);
    }
}

