<?php
// Script pour exécuter la migration de conversion en Ariary
require_once 'includes/config.php';

try {
    // Lire le contenu du fichier de migration
    $migrationSql = file_get_contents('migrations/2024_convert_to_ariary.sql');
    
    // Exécuter les requêtes une par une
    $queries = explode(';', $migrationSql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                echo "Query exécutée avec succès: " . substr($query, 0, 100) . "...\n";
            } catch (PDOException $e) {
                echo "Erreur lors de l'exécution de la requête: " . $e->getMessage() . "\n";
                echo "Requête problématique: " . substr($query, 0, 200) . "...\n\n";
            }
        }
    }
    
    echo "\nMigration terminée avec succès !\n";
    
} catch (Exception $e) {
    die("Erreur lors de l'exécution de la migration: " . $e->getMessage() . "\n");
}

echo "\nLa conversion des montants en Ariary a été effectuée avec succès.\n";
echo "Vous pouvez maintenant accéder à votre application avec les montants en Ariary.\n";
?>
