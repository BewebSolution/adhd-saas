<?php ob_start(); ?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0"><i class="fas fa-cog"></i> Gestione Liste</h1>
        <p class="text-muted">Configura i valori delle liste a tendina</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-priorita">Priorità</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-stato">Stato</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-persona">Persona</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tipo_consegna">Tipo Consegna</button>
            </li>
        </ul>

        <div class="tab-content">
            <?php
            $tabs = [
                'priorita' => 'Priorità',
                'stato' => 'Stato',
                'persona' => 'Persona',
                'tipo_consegna' => 'Tipo Consegna'
            ];

            $isFirst = true;
            foreach ($tabs as $listName => $label):
                $items = $allLists[$listName] ?? [];
            ?>
                <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?>" id="tab-<?= $listName ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5><?= $label ?></h5>
                            <form class="input-group" onsubmit="return addItem(event, '<?= $listName ?>')">
                                <input type="text" class="form-control" placeholder="Nuovo valore..."
                                       id="new-<?= $listName ?>" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Aggiungi
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Valore</th>
                                    <th class="text-end" style="width: 100px">Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="list-<?= $listName ?>">
                                <?php foreach ($items as $item): ?>
                                    <tr id="item-<?= $item['id'] ?>">
                                        <td>
                                            <span class="value"><?= esc($item['value']) ?></span>
                                            <input type="text" class="form-control form-control-sm d-none edit-input"
                                                   value="<?= esc($item['value']) ?>">
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button onclick="editItem(<?= $item['id'] ?>)" class="btn btn-outline-secondary edit-btn">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button onclick="saveItem(<?= $item['id'] ?>)" class="btn btn-success save-btn d-none">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="cancelEdit(<?= $item['id'] ?>)" class="btn btn-secondary cancel-btn d-none">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button onclick="deleteItem(<?= $item['id'] ?>)" class="btn btn-outline-danger delete-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
                $isFirst = false;
            endforeach;
            ?>
        </div>
    </div>
</div>

<script>
function addItem(e, listName) {
    e.preventDefault();

    const input = document.getElementById(`new-${listName}`);
    const value = input.value.trim();

    if (!value) return false;

    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('list_name', listName);
    formData.append('value', value);

    fetch('/settings/lists/add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            input.value = '';
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });

    return false;
}

function editItem(id) {
    const row = document.getElementById(`item-${id}`);
    row.querySelector('.value').classList.add('d-none');
    row.querySelector('.edit-input').classList.remove('d-none');
    row.querySelector('.edit-btn').classList.add('d-none');
    row.querySelector('.save-btn').classList.remove('d-none');
    row.querySelector('.cancel-btn').classList.remove('d-none');
    row.querySelector('.delete-btn').classList.add('d-none');
}

function cancelEdit(id) {
    const row = document.getElementById(`item-${id}`);
    row.querySelector('.value').classList.remove('d-none');
    row.querySelector('.edit-input').classList.add('d-none');
    row.querySelector('.edit-btn').classList.remove('d-none');
    row.querySelector('.save-btn').classList.add('d-none');
    row.querySelector('.cancel-btn').classList.add('d-none');
    row.querySelector('.delete-btn').classList.remove('d-none');
}

function saveItem(id) {
    const row = document.getElementById(`item-${id}`);
    const newValue = row.querySelector('.edit-input').value.trim();

    if (!newValue) {
        showToast('Il valore non può essere vuoto', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('value', newValue);

    fetch(`/settings/lists/${id}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            row.querySelector('.value').textContent = newValue;
            cancelEdit(id);
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}

function deleteItem(id) {
    if (!confirm('Eliminare questo valore? Attenzione: potrebbe essere in uso.')) return;

    fetch(`/settings/lists/${id}/delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrf_token() ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            document.getElementById(`item-${id}`).remove();
        } else {
            showToast(data.error || 'Errore', 'error');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$title = 'Gestione Liste - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
