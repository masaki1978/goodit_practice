<?php
$selectedCity = isset($_GET['city']) ? $_GET['city'] : '';
$selectedTown = isset($_GET['town']) ? $_GET['town'] : '';

$townData = json_decode(file_get_contents('town_data.json'), true);
$dropdownData = json_decode(file_get_contents('dropdown_data.json'), true);
$landDropdownData = json_decode(file_get_contents('land_dropdown_data.json'), true);

$propertyType = $_GET['propertyType'] ?? '';
$prefecture = $_GET['prefecture'] ?? '';

$dropdownOptions = [
    '建物面積（おおよそ）',
    '土地面積（おおよそ）',
    '間取り',
    '築年数',
    '現況',
    '売却希望時期',
    '物件とのご関係',
    'ご依頼の理由（複数選択可）'
];
$landDropdownOptions = [
    '土地面積（おおよそ）',
    '主な地目',
    '現況',
    '売却希望時期',
    'ご依頼の理由'
];
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物件査定チャット</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>


    <div class="chat-container">
        <!-- Chat introduction messages -->
        <div class="chat-message">
            <p>これから私がお客様の入力のお手伝いをさせていただきます。どうぞ宜しくお願いします。</p>
        </div>
        <div class="chat-message">
            <p>査定する物件について教えてください。</p>
        </div>

        <!-- 全体を一つのフォームにまとめる -->
        <form id="chatForm">
            <label for="propertyType">物件種別を選択:</label>
            <select id="propertyType" name="propertyType" onchange="checkSelections()">
                <option value="" disabled <?php echo !$propertyType ? 'selected' : ''; ?>>物件種別を選択</option>
                <option value="一戸建て" <?php echo $propertyType === '一戸建て' ? 'selected' : ''; ?>>一戸建て</option>
                <option value="土地" <?php echo $propertyType === '土地' ? 'selected' : ''; ?>>土地</option>
                <option value="マンション" <?php echo $propertyType === 'マンション' ? 'selected' : ''; ?>>マンション</option>
            </select>

            <label for="prefecture">都道府県を選択:</label>
            <select id="prefecture" name="prefecture" onchange="updateCities(); checkSelections();">
                <option value="" disabled <?php echo !$prefecture ? 'selected' : ''; ?>>都道府県を選択</option>
                <?php
                foreach (array_keys($townData) as $prefectureOption) {
                    $selected = $prefecture === $prefectureOption ? 'selected' : '';
                    echo "<option value=\"$prefectureOption\" $selected>$prefectureOption</option>";
                }
                ?>
            </select>

            <label for="city">市町村を選択:</label>
            <select id="city" name="city" onchange="updateTowns(); checkSelections();">
                <option value="" disabled <?php echo !$selectedCity ? 'selected' : ''; ?>>市町村を選択</option>
                <?php
                if ($prefecture && isset($townData[$prefecture])) {
                    foreach (array_keys($townData[$prefecture]) as $cityOption) {
                        $selected = $selectedCity === $cityOption ? 'selected' : '';
                        echo "<option value=\"$cityOption\" $selected>$cityOption</option>";
                    }
                }
                ?>
            </select>

            <label for="town">町名を選択:</label>
            <select id="town" name="town" onchange="checkSelections()">
                <option value="" disabled <?php echo !$selectedTown ? 'selected' : ''; ?>>町名を選択</option>
                <?php
                if ($selectedCity && isset($townData[$prefecture][$selectedCity])) {
                    foreach ($townData[$prefecture][$selectedCity] as $townOption) {
                        $selected = $selectedTown === $townOption ? 'selected' : '';
                        echo "<option value=\"$townOption\" $selected>$townOption</option>";
                    }
                }
                ?>
            </select>

            <div id="propertyDetails" style="display: none;">
                <p id="selectionSummary" style="font-weight: bold;"></p>
                <div id="confirmationButtons" style="margin-top: 10px;">
                    <button type="button" id="yesButton">はい</button>
                    <button type="button" id="noButton">いいえ</button>
                </div>
            </div>


            <div id="additionalInfo" style="display: none;">
                <p>物件についても教えてください。</p>

                <!-- 一戸建て用のドロップダウン -->
                <?php if ($propertyType === '一戸建て'): ?>
                    <?php foreach ($dropdownOptions as $index => $label): ?>
                        <div id="dropdown<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                            <label for="option<?php echo $index; ?>"><?php echo $label; ?>:</label>
                            <select id="option<?php echo $index; ?>"
                                name="option<?php echo $index; ?><?php echo $label === 'ご依頼の理由（複数選択可）' ? '[]' : ''; ?>"
                                <?php echo $label === 'ご依頼の理由（複数選択可）' ? 'multiple' : ''; ?>
                                onchange="showNextDropdown(<?php echo $index; ?>)">
                                <option value="" disabled selected>選択してください</option>
                                <?php
                                $options = $dropdownData[$label];
                                foreach ($options as $option) {
                                    echo "<option value=\"$option\">$option</option>";
                                }
                                ?>
                            </select>
                            <?php if ($label === 'ご依頼の理由（複数選択可）'): ?>
                                <button type="button" onclick="proceedToNext()">選択しました</button>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

                <!-- 土地用のドロップダウン -->
                <?php if ($propertyType === '土地'): ?>
                    <?php foreach ($landDropdownOptions as $index => $label): ?>
                        <div id="landDropdown<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                            <label for="landOption<?php echo $index; ?>"><?php echo $label; ?>:</label>
                            <select id="landOption<?php echo $index; ?>"
                                name="landOption<?php echo $index; ?><?php echo $label === 'ご依頼の理由' ? '[]' : ''; ?>"
                                <?php echo $label === 'ご依頼の理由' ? 'multiple' : ''; ?>
                                onchange="showNextDropdown(<?php echo $index; ?>)">
                                <option value="" disabled selected>選択してください</option>
                                <?php
                                $options = $label === 'ご依頼の理由' ? $landDropdownData['ご依頼の理由（複数選択可）'] : $landDropdownData[$label];
                                if (is_array($options)) {
                                    foreach ($options as $option) {
                                        echo "<option value=\"$option\">$option</option>";
                                    }
                                } else {
                                    echo "<option value=\"\">データが見つかりません</option>";
                                }
                                ?>
                            </select>
                            <!-- 「ご依頼の理由」が選択された場合にボタンを表示 -->
                            <?php if ($label === 'ご依頼の理由'): ?>
                                <button type="button" onclick="proceedToNext()">選択しました</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="finalSelection" style="display: none; margin-top: 20px;">
                <p>選択された項目:</p>
                <ul id="selectionOutput"></ul>
            </div>

            <div class="chat-message" id="addressMessage" style="display: none;">
                <p>ありがとうございます。査定額が大きくずれないように番地まで教えていただけますか？</p>
            </div>

            <!-- 番地入力フィールド -->
            <div class="" id="addressInput" style="display: none;">
                <label for="addressDetail">丁目・字・番地:</label>
                <input type="text" id="addressDetail" name="addressDetail" placeholder="丁目・字・番地を入力" style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
            </div>

            <!-- 新しいメッセージ -->
            <div class="chat-message" id="residenceConfirmMessage" style="display: none;">
                <p>お住まいもこちらでしょうか？</p>
            </div>

            <!-- 回答ボタン -->
            <div id="residenceConfirmButtons" style="display: none; margin-top: 10px;">
                <button type="button" id="yesResidenceButton">はい</button>
                <button type="button" id="noResidenceButton">いいえ</button>
            </div>

            <!-- 価格予想メッセージ -->
            <div class="chat-message" id="priceEstimateMessage" style="display: none;">
                <p>あなたの不動産の価値はいくらだと思いますか？<br>結果を予想してみましょう。</p>
            </div>

            <!-- 希望価格入力フォーム -->
            <div id="desiredPriceInput" style="display: none;">
                <label for="desiredPrice">希望価格:</label>
                <input type="text" id="desiredPrice" name="desiredPrice" placeholder="希望価格を入力" style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
            </div>

            <!-- 予想後のメッセージ -->
            <div class="chat-message" id="afterEstimateMessage" style="display: none;">
                <p>お客様の場合、意外な金額が出るかもしれないですよ！<br>いくらなのか楽しみですね。</p>
            </div>

            <!-- 気持ち確認メッセージ -->
            <div class="chat-message" id="feelingConfirmMessage" style="display: none;">
                <p>今のお気持ちはどれに近いですか？</p>
            </div>

            <!-- 気持ち選択ボタン -->
            <div id="feelingButtons" style="display: none; margin-top: 10px;">
                <button type="button" id="infoGatherButton">まずは情報収集</button>
                <button type="button" id="considerButton">高く売れそうだったら検討</button>
                <button type="button" id="sellNowButton">すぐに売却したい</button>
            </div>

            <!-- 結果通知方法の確認 -->
            <div class="chat-message" id="resultNotificationMessage" style="display: none;">
                <p>結果はどちらにお知らせしましょう？</p>
            </div>

            <!-- 名前入力フォーム -->
            <div id="nameInput" style="display: none;">
                <div>
                    <label for="lastNameKanji">お名前（姓・漢字）:</label>
                    <input type="text" id="lastNameKanji" name="lastNameKanji" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
                </div>
                <div>
                    <label for="firstNameKanji">お名前（名・漢字）:</label>
                    <input type="text" id="firstNameKanji" name="firstNameKanji" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
                </div>
                <div>
                    <label for="lastNameKana">ふりがな（姓・かな）:</label>
                    <input type="text" id="lastNameKana" name="lastNameKana" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
                </div>
                <div>
                    <label for="firstNameKana">ふりがな（名・かな）:</label>
                    <input type="text" id="firstNameKana" name="firstNameKana" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
                </div>
            </div>

            <!-- 連絡方法選択ボタン -->
            <div id="contactMethodButtons" style="display: none;">
                <button type="button" id="emailButton">メールアドレスで連絡</button>
                <button type="button" id="phoneButton">携帯電話番号で連絡</button>
            </div>

            <!-- メールアドレス入力フォーム -->
            <div id="emailInput" style="display: none;">
                <label for="email">メールアドレス:</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
            </div>

            <!-- 携帯電話番号入力フォーム -->
            <div id="phoneInput" style="display: none;">
                <label for="phone">携帯電話番号:</label>
                <input type="tel" id="phone" name="phone" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box;">
            </div>

            <!-- 最終メッセージ -->
            <div class="chat-message" id="finalMessage" style="display: none;">
                <p>お疲れ様でした！<br>ページ下の「無料で査定する」ボタンを押してください。</p>
            </div>

            <!-- 個人情報取り扱い同意チェックボックス -->
            <div id="privacyCheck" style="display: none;">
                <input type="checkbox" id="privacyAgreement" name="privacyAgreement" required>
                <label for="privacyAgreement">個人情報の取り扱いについて確認しました。</label>
            </div>

            <!-- 送信ボタン -->
            <div id="submitButtonContainer" style="display: none;">
                <button type="submit" id="submitButton" disabled>無料で査定する</button>
            </div>

        </form>
    </div>


    <script>
        function checkSelections() {
            const propertyType = document.getElementById('propertyType').value;
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const town = document.getElementById('town').value;

            if (propertyType && prefecture && city && town) {
                const selectionSummary = document.getElementById('selectionSummary');
                selectionSummary.textContent = `"${prefecture}${city}"の"${town}"の"${propertyType}"の査定をご希望ですね。`;

                document.getElementById('propertyDetails').style.display = 'block';
            }
        }

        function updateCities() {
            const prefecture = document.getElementById('prefecture').value;
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '<option value="" disabled selected>市町村を選択</option>';
            const towns = <?php echo json_encode($townData); ?>;
            if (towns[prefecture]) {
                Object.keys(towns[prefecture]).forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        }

        function updateTowns() {
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const townSelect = document.getElementById('town');
            townSelect.innerHTML = '<option value="" disabled selected>町名を選択</option>';
            const towns = <?php echo json_encode($townData); ?>;
            if (towns[prefecture] && towns[prefecture][city]) {
                towns[prefecture][city].forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    townSelect.appendChild(option);
                });
            }
        }

        function proceedToNext() {
            displaySelections(); // 選択された項目を表示

            // finalSelectionを表示
            document.getElementById('finalSelection').style.display = 'block';
            scrollToElement(document.getElementById('finalSelection')); // finalSelectionにスクロール

            // 一定時間後に次のメッセージを表示
            setTimeout(function() {
                document.getElementById('addressMessage').style.display = 'block';
                scrollToElement(document.getElementById('addressMessage')); // メッセージにスクロール
            }, 1000); // 1秒後にメッセージ表示

            // 一定時間後に番地入力フィールドを表示
            setTimeout(function() {
                document.getElementById('addressInput').style.display = 'block';
                scrollToElement(document.getElementById('addressInput')); // 入力フィールドにスクロール
            }, 2000); // 2秒後に入力フィールド表示
        }

        function scrollToElement(element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center' // 画面の中央に要素を配置する
            });
        }

        function displaySelections() {
            const selectionOutput = document.getElementById('selectionOutput');
            selectionOutput.innerHTML = '';

            // 物件種別、都道府県、市町村、町名を出力
            const propertyType = document.getElementById('propertyType').value;
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const town = document.getElementById('town').value;

            if (propertyType && prefecture && city && town) {
                const li1 = document.createElement('li');
                li1.textContent = '物件種別: ' + propertyType;
                selectionOutput.appendChild(li1);

                const li2 = document.createElement('li');
                li2.textContent = '都道府県: ' + prefecture;
                selectionOutput.appendChild(li2);

                const li3 = document.createElement('li');
                li3.textContent = '市町村: ' + city;
                selectionOutput.appendChild(li3);

                const li4 = document.createElement('li');
                li4.textContent = '町名: ' + town;
                selectionOutput.appendChild(li4);
            }

            // 一戸建て用と土地用の選択肢を動的に取得
            const options = propertyType === '一戸建て' ? <?php echo json_encode($dropdownOptions); ?> : <?php echo json_encode($landDropdownOptions); ?>;
            options.forEach((label, index) => {
                const selectElement = propertyType === '一戸建て' ?
                    document.getElementById('option' + index) :
                    document.getElementById('landOption' + index);
                if (selectElement) {
                    // 複数選択対応
                    if (selectElement.multiple) {
                        const selectedOptions = Array.from(selectElement.selectedOptions).map(option => option.value);
                        if (selectedOptions.length > 0) {
                            const li = document.createElement('li');
                            li.textContent = label + ': ' + selectedOptions.join(', ');
                            selectionOutput.appendChild(li);
                        }
                    } else {
                        const selectedOption = selectElement.value;
                        if (selectedOption) {
                            const li = document.createElement('li');
                            li.textContent = label + ': ' + selectedOption;
                            selectionOutput.appendChild(li);
                        }
                    }
                }
            });
        }

        function showNextDropdown(currentIndex) {
            const nextIndex = currentIndex + 1;
            const nextDropdown = document.getElementById('landDropdown' + nextIndex) || document.getElementById('dropdown' + nextIndex);
            if (nextDropdown) {
                nextDropdown.style.display = 'block';
                scrollToElement(nextDropdown);
            } else {
                displaySelections();
            }
        }

        function showResidenceConfirmation() {
            residenceConfirmMessage.style.display = 'block';
            residenceConfirmButtons.style.display = 'block';
            scrollToElement(residenceConfirmMessage);
        }

        function showPriceEstimateMessage() {
            priceEstimateMessage.style.display = 'block';
            desiredPriceInput.style.display = 'block';
            scrollToElement(priceEstimateMessage);
        }

        function showAfterEstimateMessage() {
            afterEstimateMessage.style.display = 'block';
            scrollToElement(afterEstimateMessage);

            setTimeout(() => {
                feelingConfirmMessage.style.display = 'block';
                feelingButtons.style.display = 'block';
                scrollToElement(feelingConfirmMessage);
            }, 2000);
        }

        function handleFeelingSelection() {
            console.log('気持ちが選択されました:', this.textContent);
            showResultNotificationMessage();
        }



        function showResultNotificationMessage() {
            document.getElementById('resultNotificationMessage').style.display = 'block';
            document.getElementById('nameInput').style.display = 'block';
            scrollToElement(document.getElementById('resultNotificationMessage'));
        }

        function showContactMethodButtons() {
            document.getElementById('contactMethodButtons').style.display = 'block';
            scrollToElement(document.getElementById('contactMethodButtons'));
        }

        function showEmailInput() {
            document.getElementById('emailInput').style.display = 'block';
            document.getElementById('phoneInput').style.display = 'none';
            scrollToElement(document.getElementById('emailInput'));
        }

        function showPhoneInput() {
            document.getElementById('phoneInput').style.display = 'block';
            document.getElementById('emailInput').style.display = 'none';
            scrollToElement(document.getElementById('phoneInput'));
        }

        function showFinalMessage() {
            document.getElementById('finalMessage').style.display = 'block';
            document.getElementById('privacyCheck').style.display = 'block';
            document.getElementById('submitButtonContainer').style.display = 'block';
            scrollToElement(document.getElementById('finalMessage'));
        }

        function validateEmail(email) {
            const re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
            return re.test(String(email).toLowerCase());
        }

        function validatePhone(phone) {
            const re = /^[0-9]{10,11}$/;
            return re.test(String(phone).replace(/[-\s]/g, ''));
        }


        // すべてのDOM操作とイベントリスナーを1つのDOMContentLoadedイベントにまとめる
        document.addEventListener('DOMContentLoaded', function() {
            // 要素の取得
            const yesButton = document.getElementById('yesButton');
            const noButton = document.getElementById('noButton');
            const additionalInfo = document.getElementById('additionalInfo');
            const chatForm = document.getElementById('chatForm');
            const propertyDetails = document.getElementById('propertyDetails');
            const addressDetailInput = document.getElementById('addressDetail');
            const residenceConfirmMessage = document.getElementById('residenceConfirmMessage');
            const residenceConfirmButtons = document.getElementById('residenceConfirmButtons');
            const yesResidenceButton = document.getElementById('yesResidenceButton');
            const noResidenceButton = document.getElementById('noResidenceButton');
            const priceEstimateMessage = document.getElementById('priceEstimateMessage');
            const desiredPriceInput = document.getElementById('desiredPriceInput');
            const desiredPrice = document.getElementById('desiredPrice');
            const afterEstimateMessage = document.getElementById('afterEstimateMessage');
            const feelingConfirmMessage = document.getElementById('feelingConfirmMessage');
            const feelingButtons = document.getElementById('feelingButtons');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');

            // イベントリスナーの設定
            yesButton.addEventListener('click', function() {
                propertyDetails.style.display = 'none';
                additionalInfo.style.display = 'block';
                scrollToElement(additionalInfo);
            });

            noButton.addEventListener('click', function() {
                chatForm.style.display = 'block';
                propertyDetails.style.display = 'none';
                scrollToElement(chatForm);
            });

            addressDetailInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (addressDetailInput.value.trim() !== '') {
                        showResidenceConfirmation();
                    }
                }
            });

            yesResidenceButton.addEventListener('click', function() {
                console.log('住所が確認されました');
                showPriceEstimateMessage();
            });

            noResidenceButton.addEventListener('click', function() {
                console.log('住所が異なります');
                // ここに別の住所を入力するための処理を追加
            });

            desiredPrice.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (desiredPrice.value.trim() !== '') {
                        showAfterEstimateMessage();
                    }
                }
            });

            // 気持ち選択ボタンのイベントリスナー
            document.getElementById('infoGatherButton').addEventListener('click', handleFeelingSelection);
            document.getElementById('considerButton').addEventListener('click', handleFeelingSelection);
            document.getElementById('sellNowButton').addEventListener('click', handleFeelingSelection);

            // 名前入力フォームのイベントリスナー
            document.getElementById('firstNameKana').addEventListener('change', showEmailInput);

            // メールアドレス入力フォームのイベントリスナー
            email.addEventListener('change', function() {
                if (validateEmail(this.value)) {
                    showPhoneInput();
                } else {
                    alert('有効なメールアドレスを入力してください。');
                }
            });

            // 携帯電話番号入力フォームのイベントリスナー
            phone.addEventListener('change', function() {
                if (validatePhone(this.value)) {
                    showFinalMessage();
                } else {
                    alert('有効な携帯電話番号を入力してください。');
                }
            });

            // 個人情報取り扱い同意チェックボックスのイベントリスナー
            document.getElementById('privacyAgreement').addEventListener('change', function() {
                document.getElementById('submitButton').disabled = !this.checked;
            });

            // フォーム送信のイベントリスナー
            document.getElementById('chatForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // フォームデータの送信処理
                const formData = new FormData(this);
                fetch('mailto:masaki1978@gmail.com', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            alert('査定依頼が送信されました。');
                        } else {
                            alert('送信に失敗しました。もう一度お試しください。');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('エラーが発生しました。もう一度お試しください。');
                    });
            });
        });

        function checkSelections() {
            const propertyType = document.getElementById('propertyType').value;
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const town = document.getElementById('town').value;

            if (propertyType && prefecture && city && town) {
                const selectionSummary = document.getElementById('selectionSummary');
                selectionSummary.textContent = `"${prefecture}${city}"の"${town}"の"${propertyType}"の査定をご希望ですね。`;

                document.getElementById('propertyDetails').style.display = 'block';
            }
        }

        function updateCities() {
            const prefecture = document.getElementById('prefecture').value;
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '<option value="" disabled selected>市町村を選択</option>';
            const towns = <?php echo json_encode($townData); ?>;
            if (towns[prefecture]) {
                Object.keys(towns[prefecture]).forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        }

        function updateTowns() {
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const townSelect = document.getElementById('town');
            townSelect.innerHTML = '<option value="" disabled selected>町名を選択</option>';
            const towns = <?php echo json_encode($townData); ?>;
            if (towns[prefecture] && towns[prefecture][city]) {
                towns[prefecture][city].forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    townSelect.appendChild(option);
                });
            }
        }

        function proceedToNext() {
            displaySelections();

            document.getElementById('finalSelection').style.display = 'block';
            scrollToElement(document.getElementById('finalSelection'));

            setTimeout(function() {
                document.getElementById('addressMessage').style.display = 'block';
                scrollToElement(document.getElementById('addressMessage'));
            }, 1000);

            setTimeout(function() {
                document.getElementById('addressInput').style.display = 'block';
                scrollToElement(document.getElementById('addressInput'));
            }, 2000);
        }

        function scrollToElement(element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function displaySelections() {
            const selectionOutput = document.getElementById('selectionOutput');
            selectionOutput.innerHTML = '';

            const propertyType = document.getElementById('propertyType').value;
            const prefecture = document.getElementById('prefecture').value;
            const city = document.getElementById('city').value;
            const town = document.getElementById('town').value;

            if (propertyType && prefecture && city && town) {
                const li1 = document.createElement('li');
                li1.textContent = '物件種別: ' + propertyType;
                selectionOutput.appendChild(li1);

                const li2 = document.createElement('li');
                li2.textContent = '都道府県: ' + prefecture;
                selectionOutput.appendChild(li2);

                const li3 = document.createElement('li');
                li3.textContent = '市町村: ' + city;
                selectionOutput.appendChild(li3);

                const li4 = document.createElement('li');
                li4.textContent = '町名: ' + town;
                selectionOutput.appendChild(li4);
            }

            const options = propertyType === '一戸建て' ? <?php echo json_encode($dropdownOptions); ?> : <?php echo json_encode($landDropdownOptions); ?>;
            options.forEach((label, index) => {
                const selectElement = propertyType === '一戸建て' ?
                    document.getElementById('option' + index) :
                    document.getElementById('landOption' + index);
                if (selectElement) {
                    if (selectElement.multiple) {
                        const selectedOptions = Array.from(selectElement.selectedOptions).map(option => option.value);
                        if (selectedOptions.length > 0) {
                            const li = document.createElement('li');
                            li.textContent = label + ': ' + selectedOptions.join(', ');
                            selectionOutput.appendChild(li);
                        }
                    } else {
                        const selectedOption = selectElement.value;
                        if (selectedOption) {
                            const li = document.createElement('li');
                            li.textContent = label + ': ' + selectedOption;
                            selectionOutput.appendChild(li);
                        }
                    }
                }
            });
        }

        function showNextDropdown(currentIndex) {
            const nextIndex = currentIndex + 1;
            const nextDropdown = document.getElementById('landDropdown' + nextIndex) || document.getElementById('dropdown' + nextIndex);
            if (nextDropdown) {
                nextDropdown.style.display = 'block';
                scrollToElement(nextDropdown);
            } else {
                displaySelections();
            }
        }

        function showResidenceConfirmation() {
            residenceConfirmMessage.style.display = 'block';
            residenceConfirmButtons.style.display = 'block';
            scrollToElement(residenceConfirmMessage);
        }

        function showPriceEstimateMessage() {
            priceEstimateMessage.style.display = 'block';
            desiredPriceInput.style.display = 'block';
            scrollToElement(priceEstimateMessage);
        }

        function showAfterEstimateMessage() {
            afterEstimateMessage.style.display = 'block';
            scrollToElement(afterEstimateMessage);

            setTimeout(() => {
                feelingConfirmMessage.style.display = 'block';
                feelingButtons.style.display = 'block';
                scrollToElement(feelingConfirmMessage);
            }, 2000);
        }

        function handleFeelingSelection() {
            console.log('気持ちが選択されました:', this.textContent);
            showResultNotificationMessage();
        }

        function showResultNotificationMessage() {
            document.getElementById('resultNotificationMessage').style.display = 'block';
            document.getElementById('nameInput').style.display = 'block';
            scrollToElement(document.getElementById('resultNotificationMessage'));
        }

        function showEmailInput() {
            document.getElementById('emailInput').style.display = 'block';
            scrollToElement(document.getElementById('emailInput'));
        }

        function showPhoneInput() {
            document.getElementById('phoneInput').style.display = 'block';
            scrollToElement(document.getElementById('phoneInput'));
        }

        function showFinalMessage() {
            document.getElementById('finalMessage').style.display = 'block';
            document.getElementById('privacyCheck').style.display = 'block';
            document.getElementById('submitButtonContainer').style.display = 'block';
            scrollToElement(document.getElementById('finalMessage'));
        }

        function validateEmail(email) {
            const re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
            return re.test(String(email).toLowerCase());
        }

        function validatePhone(phone) {
            const re = /^[0-9]{10,11}$/;
            return re.test(String(phone).replace(/[-\s]/g, ''));
        }
    </script>


</body>

</html>