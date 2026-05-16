<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $statut = $_POST['nouveau_statut'] ?? '';

    $statuts_valides = ['En attente', 'En soin', 'Sorti'];

    if ($id && in_array($statut, $statuts_valides, true)) {
        try {
            if ($statut === 'Sorti') {
                $sql = "UPDATE patients_urgence
                        SET statut = :statut, heure_sortie = NOW()
                        WHERE id = :id";
            } else {
                $sql = "UPDATE patients_urgence
                        SET statut = :statut, heure_sortie = NULL
                        WHERE id = :id";
            }
            $pdo->prepare($sql)->execute([':statut' => $statut, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('Erreur mise à jour statut : ' . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit();
?>
