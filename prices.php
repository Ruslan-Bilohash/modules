<?php
$prices = $conn->query("SELECT * FROM prices LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="mb-4">Прайсы</h1>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Пользователь</th>
            <th>Категория</th>
            <th>Название</th>
            <th>Цена</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($prices as $price): ?>
            <tr>
                <td><?php echo $price['id']; ?></td>
                <td><?php echo $price['user_id']; ?></td>
                <td><?php echo $price['category_id']; ?></td>
                <td><?php echo $price['title']; ?></td>
                <td><?php echo $price['price']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>