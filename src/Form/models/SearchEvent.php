<?php

namespace App\Form\models;

use App\Entity\Campus;
use App\Entity\Participant;

class SearchEvent
{
    private ?Campus $campus = null;
    private ?string $nom = null;
    private ?\DateTime $startDate = null;
    private ?\DateTime $endDate = null;
    private ?bool $sortieOrganisateur = null;
    private ?bool $sortiesInscrits = null;
    private ?bool $sortiesNonInscrits = null;
    private ?bool $sortiesPassees = null;

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): void
    {
        $this->campus = $campus;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): void
    {
        $this->nom = $nom;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getSortieOrganisateur(): ?bool
    {
        return $this->sortieOrganisateur;
    }

    public function setSortieOrganisateur(?bool $sortieOrganisateur): void
    {
        $this->sortieOrganisateur = $sortieOrganisateur;
    }

    public function getSortiesInscrits(): ?bool
    {
        return $this->sortiesInscrits;
    }

    public function setSortiesInscrits(?bool $sortiesInscrits): void
    {
        $this->sortiesInscrits = $sortiesInscrits;
    }

    public function getSortiesNonInscrits(): ?bool
    {
        return $this->sortiesNonInscrits;
    }

    public function setSortiesNonInscrits(?bool $sortiesNonInscrits): void
    {
        $this->sortiesNonInscrits = $sortiesNonInscrits;
    }

    public function getSortiesPassees(): ?bool
    {
        return $this->sortiesPassees;
    }

    public function setSortiesPassees(?bool $sortiesPassees): void
    {
        $this->sortiesPassees = $sortiesPassees;
    }

}