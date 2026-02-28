<?php 
    include_once '../config.php';
    $db = new Database();
    $oqituvchilar = $db->get_oqtuvchilar();
?>
<?php foreach ($oqituvchilar as $oqituvchi): ?>
    <tr>
        <td><?php echo htmlspecialchars($oqituvchi['id']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['fio']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['fakultet_name']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['kafedra_name']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['lavozim']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['stavka']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['ishtur_name']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['ilmiy_unvon_name']); ?></td>
        <td><?php echo htmlspecialchars($oqituvchi['ilmiy_daraja_name']); ?></td>
        <td>
            <button class="btn btn-sm btn-warning editOqituvchiBtn" data-id="<?php echo $oqituvchi['id']; ?>">
                <i class="fas fa-edit"></i> Tahrirlash
            </button>
            <button class="btn btn-sm btn-danger deleteOqituvchiBtn" data-id="<?php echo $oqituvchi['id']; ?>">
                <i class="fas fa-trash-alt"></i> O'chirish
            </button>
        </td>
    </tr>
<?php endforeach; ?>