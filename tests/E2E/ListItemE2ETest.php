<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

/**
 * Tests end-to-end (Panther + ChromeDriver) pour la gestion des tâches (ListItem).
 *
 * Panther démarre un vrai serveur HTTP (séparé du processus PHPUnit),
 * donc on crée toutes les données via le navigateur (pas via l'EntityManager).
 *
 * Lancer avec :
 *   php bin/phpunit tests/E2E/ListItemE2ETest.php --testdox
 */
final class ListItemE2ETest extends PantherTestCase
{
    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Crée une tâche via le formulaire navigateur avec le titre donné.
     * Retourne l'URL de la page de détail après redirection (ex. http://.../list/item).
     */
    private function créerTâcheViaBrowser(\Symfony\Component\Panther\Client $client, string $titre): void
    {
        $client->request('GET', '/list/item/new');
        $client->waitFor('input[name="list_item[title]"]');
        $client->submitForm('Enregistrer', [
            'list_item[title]' => $titre,
        ]);
        // Attendre la redirection
        $client->waitFor('h1');
    }

    /**
     * Trouve l'URL de détail d'une tâche par son titre dans la liste.
     */
    private function trouverUrlTâche(\Symfony\Component\Panther\Client $client, string $titre): ?string
    {
        $crawler = $client->request('GET', '/list/item');
        $client->waitFor('body');

        $allRows = $crawler->filter('tbody tr');
        for ($i = 0; $i < $allRows->count(); $i++) {
            $row     = $allRows->eq($i);
            $rowText = $row->text('', true);
            if (str_contains($rowText, $titre)) {
                return $row->filter('a[href*="/list/item/"]')->first()->attr('href');
            }
        }
        return null;
    }

    // ---------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------

    /**
     * La page liste des tâches retourne 200 et affiche le bon titre.
     */
    public function testPageListeTachesEstAccessible(): void
    {
        $client  = static::createPantherClient();
        $crawler = $client->request('GET', '/list/item');
        $client->waitFor('h1');

        $this->assertSame(200, $client->getInternalResponse()->getStatusCode());
        $this->assertStringContainsStringIgnoringCase(
            'tâche',
            $client->getCrawler()->filter('h1')->text(),
            'Le titre h1 doit contenir le mot "tâche".'
        );
    }

    /**
     * Le formulaire de création expose bien un champ titre.
     */
    public function testFormulaireCréationContientChampTitre(): void
    {
        $client  = static::createPantherClient();
        $crawler = $client->request('GET', '/list/item/new');
        $client->waitFor('input[name="list_item[title]"]');

        $this->assertSame(200, $client->getInternalResponse()->getStatusCode());
        $this->assertGreaterThan(
            0,
            $client->getCrawler()->filter('input[name="list_item[title]"]')->count(),
            'Le champ list_item[title] est absent du formulaire.'
        );
    }

    /**
     * Création d'une tâche avec un titre valide → on est redirigé vers /list/item.
     */
    public function testCréationTacheValideRedirigeVersLaListe(): void
    {
        $client = static::createPantherClient();
        $this->créerTâcheViaBrowser($client, 'Tâche valide E2E ' . uniqid());

        $this->assertStringContainsString(
            '/list/item',
            $client->getCurrentURL(),
            'Après création, on devrait être sur /list/item.'
        );
    }

    /**
     * Après création, la tâche apparaît dans le tableau de la liste.
     */
    public function testTacheCréeEstVisibleDansLaListe(): void
    {
        $titre  = 'Tâche visible E2E ' . uniqid();
        $client = static::createPantherClient();

        $this->créerTâcheViaBrowser($client, $titre);

        // Recharger la liste et vérifier
        $crawler = $client->request('GET', '/list/item');
        $client->waitFor('body');

        $this->assertStringContainsString(
            $titre,
            $client->getCrawler()->filter('body')->text('', true),
            'La tâche créée devrait apparaître dans le tableau.'
        );
    }

    /**
     * Création d'une tâche avec un titre vide → le formulaire reste ouvert.
     */
    public function testCréationTacheAvecTitreVideResteFormulaire(): void
    {
        $client  = static::createPantherClient();
        $client->request('GET', '/list/item/new');
        $client->waitFor('input[name="list_item[title]"]');

        $client->submitForm('Enregistrer', [
            'list_item[title]' => '',
        ]);

        // On doit rester sur /list/item/new (formulaire invalide)
        $this->assertStringContainsString(
            '/list/item/new',
            $client->getCurrentURL(),
            'Un titre vide devrait maintenir le formulaire ouvert.'
        );
    }

    /**
     * Création puis édition d'une tâche : le titre mis à jour est bien visible.
     */
    public function testEditionTacheMetsÀJourLeTitre(): void
    {
        $titreOriginal = 'Edition avant ' . uniqid();
        $titreModifié  = 'Edition après ' . uniqid();
        $client        = static::createPantherClient();

        // 1. Créer la tâche
        $this->créerTâcheViaBrowser($client, $titreOriginal);

        // 2. Trouver la ligne et cliquer sur ✏️ (lien href contenant /edit)
        $editUrl = $this->trouverUrlTâche($client, $titreOriginal);
        $this->assertNotNull($editUrl, 'Aucune ligne trouvée pour la tâche créée.');

        // Construire l'URL d'édition
        $editUrl = rtrim($editUrl, '/') . '/edit';

        // 3. Naviguer sur la page d'édition
        $client->request('GET', $editUrl);
        $client->waitFor('input[name="list_item[title]"]');

        // 4. Effacer et saisir le nouveau titre via WebDriver
        $input = $client->getCrawler()->filter('input[name="list_item[title]"]')->getElement(0);
        $input->clear();
        $input->sendKeys($titreModifié);

        // Soumettre via le bouton CSS (emoji dans le label)
        $client->getCrawler()->filter('form button[type="submit"]')->getElement(0)->click();
        $client->waitFor('h1');

        // 5. Vérifier que le nouveau titre apparaît dans la liste
        $crawler = $client->request('GET', '/list/item');
        $client->waitFor('body');

        $this->assertStringContainsString(
            $titreModifié,
            $client->getCrawler()->filter('body')->text('', true),
            'Le titre mis à jour devrait apparaître dans la liste.'
        );
    }

    /**
     * Création puis suppression : la tâche disparaît de la liste.
     */
    public function testSuppressionTacheDisparaîtDeLaListe(): void
    {
        $titre  = 'Suppression E2E ' . uniqid();
        $client = static::createPantherClient();

        // 1. Créer la tâche
        $this->créerTâcheViaBrowser($client, $titre);

        // 2. Trouver le lien "Voir" de cette tâche
        $showUrl = $this->trouverUrlTâche($client, $titre);
        $this->assertNotNull($showUrl, 'Impossible de trouver le lien vers la page de détail.');

        // 3. Aller sur la page de détail
        $client->request('GET', $showUrl);
        $client->waitFor('form');

        // 4. Écraser window.confirm puis soumettre le formulaire de suppression
        //    On passe directement par form.submit() pour contourner le confirm()
        $client->executeScript('window.confirm = () => true;');
        $client->getCrawler()->filter('form button')->getElement(0)->click();

        // 5. Attendre que l'URL revienne sur la liste
        $client->waitFor('h1');
        // Naviguer explicitement sur la liste pour s'assurer d'y être
        $client->request('GET', '/list/item');
        $client->waitFor('body');

        $this->assertStringContainsString(
            '/list/item',
            $client->getCurrentURL(),
            'Après suppression on devrait être redirigé vers /list/item.'
        );

        $bodyText = $client->getCrawler()->filter('body')->text('', true);
        $this->assertStringNotContainsString(
            $titre,
            $bodyText,
            'La tâche supprimée ne devrait plus figurer dans la liste.'
        );
    }

    /**
     * La page de détail d'une tâche créée affiche son titre.
     */
    public function testPageDétailAfficheTitreDeLaTache(): void
    {
        $titre  = 'Détail E2E ' . uniqid();
        $client = static::createPantherClient();

        // Créer la tâche
        $this->créerTâcheViaBrowser($client, $titre);

        // Trouver le lien "Voir"
        $showUrl = $this->trouverUrlTâche($client, $titre);
        $this->assertNotNull($showUrl, 'Aucun lien de détail trouvé pour la tâche.');

        // Aller sur la page de détail
        $client->request('GET', $showUrl);
        $client->waitForElementToContain('body', $titre, 5);

        $this->assertStringContainsString(
            $titre,
            $client->getCrawler()->filter('body')->text('', true),
            'Le titre de la tâche doit apparaître sur la page de détail.'
        );
    }
}
