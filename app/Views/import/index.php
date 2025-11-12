<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="fas fa-upload"></i> Import CSV</h1>
        <p class="text-muted">Importa dati da file CSV esportati dal Google Sheet</p>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Come usare l'import:</strong>
    <ul class="mb-0">
        <li>Esporta i fogli dal Google Sheet in formato CSV</li>
        <li>Seleziona il tipo di dato da importare</li>
        <li>Carica il file CSV corrispondente</li>
        <li>Il sistema mapperà automaticamente le colonne e importerà i dati</li>
    </ul>
</div>

<div class="row">
    <?php
    $importTypes = [
        'tasks' => ['icon' => 'list-task', 'title' => 'Attività', 'desc' => 'Import attività con codice, titolo, progetto, etc.', 'color' => 'primary'],
        'timelogs' => ['icon' => 'clock-history', 'title' => 'Registro ore', 'desc' => 'Import time log con data, ore, persona, attività', 'color' => 'success'],
        'deliverables' => ['icon' => 'file-earmark-arrow-up', 'title' => 'Consegne', 'desc' => 'Import consegne con tipo, stato, link', 'color' => 'info'],
        'notes' => ['icon' => 'journal-text', 'title' => 'Note', 'desc' => 'Import note e decisioni', 'color' => 'warning'],
    ];

    foreach ($importTypes as $entity => $config):
    ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-<?= $config['color'] ?> text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-<?= $config['icon'] ?>"></i>
                        <?= $config['title'] ?>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $config['desc'] ?></p>

                    <form method="POST" action="<?= url('/import') ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="entity" value="<?= $entity ?>">

                        <div class="mb-3">
                            <label for="csv_<?= $entity ?>" class="form-label">File CSV</label>
                            <input type="file" class="form-control" id="csv_<?= $entity ?>"
                                   name="csv_file" accept=".csv" required>
                            <div class="form-text">Solo file CSV (formato UTF-8)</div>
                        </div>

                        <button type="submit" class="btn btn-<?= $config['color'] ?> w-100">
                            <i class="fas fa-upload"></i> Importa <?= strtolower($config['title']) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Mapping Info -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Mappatura colonne CSV</h5>
    </div>
    <div class="card-body">
        <div class="accordion" id="mappingAccordion">
            <!-- Tasks -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mapping-tasks">
                        Attività - Colonne richieste
                    </button>
                </h2>
                <div id="mapping-tasks" class="accordion-collapse collapse" data-bs-parent="#mappingAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Colonna CSV</th>
                                    <th>Campo DB</th>
                                    <th>Obbligatorio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>id / code</td><td>Codice attività</td><td>No (auto-generato)</td></tr>
                                <tr><td>data</td><td>Data</td><td>No (default: oggi)</td></tr>
                                <tr><td>progetto</td><td>Progetto</td><td><strong>Sì</strong></td></tr>
                                <tr><td>titolo</td><td>Titolo</td><td><strong>Sì</strong></td></tr>
                                <tr><td>descrizione</td><td>Descrizione</td><td>No</td></tr>
                                <tr><td>priorita / priorità</td><td>Priorità</td><td>No</td></tr>
                                <tr><td>stato</td><td>Stato</td><td>No (default: Da fare)</td></tr>
                                <tr><td>assegnatario</td><td>Assegnatario</td><td>No</td></tr>
                                <tr><td>scadenza</td><td>Data/Ora scadenza</td><td>No</td></tr>
                                <tr><td>ore_stimate</td><td>Ore stimate</td><td>No</td></tr>
                                <tr><td>link</td><td>Link</td><td>No</td></tr>
                                <tr><td>note</td><td>Note</td><td>No</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Time Logs -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mapping-timelogs">
                        Registro ore - Colonne richieste
                    </button>
                </h2>
                <div id="mapping-timelogs" class="accordion-collapse collapse" data-bs-parent="#mappingAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr><td>data</td><td>Data</td><td><strong>Sì</strong></td></tr>
                                <tr><td>persona</td><td>Persona</td><td>No</td></tr>
                                <tr><td>attivita / code</td><td>Codice attività</td><td>No (opzionale)</td></tr>
                                <tr><td>descrizione</td><td>Descrizione</td><td>No</td></tr>
                                <tr><td>ore</td><td>Ore</td><td><strong>Sì</strong></td></tr>
                                <tr><td>link_output / link</td><td>Link output</td><td>No</td></tr>
                                <tr><td>blocco</td><td>Blocco (Sì/No)</td><td>No</td></tr>
                                <tr><td>note</td><td>Note</td><td>No</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Deliverables -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mapping-deliverables">
                        Consegne - Colonne richieste
                    </button>
                </h2>
                <div id="mapping-deliverables" class="accordion-collapse collapse" data-bs-parent="#mappingAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr><td>data</td><td>Data</td><td>No (default: oggi)</td></tr>
                                <tr><td>progetto</td><td>Progetto</td><td><strong>Sì</strong></td></tr>
                                <tr><td>tipo</td><td>Tipo</td><td>No</td></tr>
                                <tr><td>titolo</td><td>Titolo</td><td><strong>Sì</strong></td></tr>
                                <tr><td>link</td><td>Link</td><td>No</td></tr>
                                <tr><td>stato</td><td>Stato</td><td>No (default: In revisione)</td></tr>
                                <tr><td>note</td><td>Note</td><td>No</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mapping-notes">
                        Note - Colonne richieste
                    </button>
                </h2>
                <div id="mapping-notes" class="accordion-collapse collapse" data-bs-parent="#mappingAccordion">
                    <div class="accordion-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr><td>data</td><td>Data</td><td>No (default: oggi)</td></tr>
                                <tr><td>tema</td><td>Tema/Topic</td><td><strong>Sì</strong></td></tr>
                                <tr><td>nota / body</td><td>Corpo nota</td><td><strong>Sì</strong></td></tr>
                                <tr><td>azione_successiva / next_action</td><td>Azione successiva</td><td>No</td></tr>
                                <tr><td>owner / responsabile</td><td>Responsabile</td><td>No</td></tr>
                                <tr><td>scadenza</td><td>Scadenza</td><td>No</td></tr>
                                <tr><td>link</td><td>Link</td><td>No</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Import CSV - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
