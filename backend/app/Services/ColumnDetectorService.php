<?php

namespace App\Services;

class ColumnDetectorService
{
    /** @var array<string, list<string>> */
    private const ALIASES = [
        'name' => ['nom', 'name', 'societe', 'company', 'raison sociale', 'business name'],
        'email' => ['email', 'e-mail', 'mail', 'courriel'],
        'phone' => ['telephone', 'phone', 'tel', 'mobile'],
        'address' => ['adresse', 'address', 'rue', 'street'],
        'city' => ['ville', 'city'],
        'zip_code' => ['code postal', 'zip', 'cp', 'postal code'],
        'department' => ['departement', 'department', 'dept'],
        'country' => ['pays', 'country'],
        'industry' => ['secteur', 'industry', 'metier', 'activite', 'profession', "secteur d'activite"],
        'notes' => ['notes', 'commentaires', 'remarks'],
        'contact.first_name' => ['prenom', 'first name', 'firstname'],
        'contact.last_name' => ['nom contact', 'last name', 'lastname'],
        'contact.position' => ['poste', 'position', 'titre', 'title'],
    ];

    /**
     * @param  list<string>  $headers
     * @return array<string, string|null>
     */
    public function detect(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $header) {
            $normalizedHeader = $this->normalize($header);
            $mapping[$header] = null;

            foreach (self::ALIASES as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if ($normalizedHeader === $this->normalize($alias)) {
                        $mapping[$header] = $field;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    private function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = str_replace(['_', '-'], ' ', $value);

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return preg_replace('/\s+/', ' ', $ascii ?: $value) ?? $value;
    }
}
