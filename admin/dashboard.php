<?php
$page_title = "Tableau de bord";
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h1>Tableau de bord</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Matchs</h5>
                        <h2>25</h2>
                    </div>
                    <div>
                        <i class="fas fa-futbol fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Commandes</h5>
                        <h2>150</h2>
                    </div>
                    <div>
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Utilisateurs</h5>
                        <h2>45</h2>
                    </div>
                    <div>
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Revenus</h5>
                        <h2>€2,340</h2>
                    </div>
                    <div>
                        <i class="fas fa-euro-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Derniers matchs ajoutés</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date</th>
                                <th>Stade</th>
                                <th>Prix</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>PSG vs Marseille</td>
                                <td>2024-12-20</td>
                                <td>Parc des Princes</td>
                                <td>€45</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">Modifier</button>
                                    <button class="btn btn-sm btn-danger">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?> 