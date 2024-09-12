<?php
$townData = json_decode(file_get_contents('town_data.json'), true);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物件問い合わせフォーム</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function updateCities() {
            const prefecture = document.getElementById('prefecture').value;
            const citySelect = document.getElementById('city');
            const townSelect = document.getElementById('town');
            const cities = <?php echo json_encode($townData); ?>[prefecture];

            citySelect.innerHTML = '<option value="" disabled selected>市町村を選択</option>';
            townSelect.innerHTML = '<option value="" disabled selected>町名を選択</option>';

            if (cities) {
                for (const city in cities) {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                }
            }
        }

        function updateTowns() {
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const townSelect = document.getElementById('town');
            const towns = <?php echo json_encode($townData); ?>[prefecture][city];

            townSelect.innerHTML = '<option value="" disabled selected>町名を選択</option>';

            if (towns) {
                towns.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    townSelect.appendChild(option);
                });
            }
        }
    </script>
</head>
<body>
    <form action="chat.php" method="GET">
        <label for="propertyType">物件種別を選択:</label>
        <select required id="propertyType" name="propertyType">
            <option value="" disabled selected >物件種別を選択</option>
            <option value="一戸建て">一戸建て</option>
            <option value="土地">土地</option>
            <option value="マンション">マンション</option>
        </select>

        <label for="prefecture">都道府県を選択:</label>
        <select required id="prefecture" name="prefecture" onchange="updateCities()">
            <option value="" disabled selected>都道府県を選択</option>
            <?php
            foreach (array_keys($townData) as $prefecture) {
                echo "<option value=\"$prefecture\">$prefecture</option>";
            }
            ?>
        </select>

        <label for="city">市町村を選択:</label>
        <select id="city" name="city" onchange="updateTowns()">
            <option value="" disabled selected>市町村を選択</option>
        </select>

        <label for="town">町名を選択:</label>
        <select id="town" name="town">
            <option value="" disabled selected>町名を選択</option>
        </select>

        <button type="submit">次へ</button>
    
    <input type="hidden" name="city" id="city_hidden">
    <input type="hidden" name="town" id="town_hidden">
</form>

</body>

<script>
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('city_hidden').value = document.getElementById('city').value;
    document.getElementById('town_hidden').value = document.getElementById('town').value;
});
</script>
</html>
