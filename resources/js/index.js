function generateUUID() {
    // 生成一个随机的UUID
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (c) {
        var r = (Math.random() * 16) | 0,
            v = c == "x" ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

function onClickGenUUID(self) {
    const outputId = self.getAttribute("data-output");
    document.getElementById(outputId).value = generateUUID();
}

function autoDarkMode() {
    const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)"),
        handleDarkModeChange = (e) => {
            e.matches
                ? document.documentElement.setAttribute("data-bs-theme", "dark")
                : document.documentElement.removeAttribute("data-bs-theme");
        };
    handleDarkModeChange(darkModeMediaQuery), darkModeMediaQuery.addListener(handleDarkModeChange);
}

function handleSelectChange() {
    // 获取所有具有 data-dynamic-select 属性的 select 元素
    const allSelects = document.querySelectorAll("[data-dynamic-select]");
    const selected = [];

    // 遍历所有 select 元素。将已选项目存入 selected 中
    allSelects.forEach((select) => {
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].selected) selected.push(select.options[i].value);
        }
    });

    allSelects.forEach((select) => {
        select.querySelectorAll("option").forEach((option) => {
            // 根据当前 select 的选中值禁用其他的 option。自己除外。
            option.disabled =
                option.value !== select.options[select.selectedIndex].value && selected.includes(option.value);
        });
    });
}

function onClickCreateNewTbody(self) {
    var table = document.getElementById(self.getAttribute("data-tableID"));
    var tbody = table.getElementsByTagName("tbody")[0];
    var lastRow = tbody.rows[tbody.rows.length - 1].cloneNode(true);

    // 清除input的值
    var inputs = lastRow.querySelectorAll("input");
    inputs.forEach(function (input) {
        input.value = "";

        // 更新id和name
        var currentNumber = parseInt(input.name.match(/\d+/)[0]);
        var newNumber = currentNumber + 1;
        input.id = input.id.replace(currentNumber, newNumber);
        input.name = input.name.replace(currentNumber, newNumber);

        if (input.type === "hidden") input.name = input.name.replace(/\[(sid|uid|gid)\]/, "[newEmpty]");
    });

    // 更新 button 的 data-output
    var button = lastRow.querySelectorAll("button");
    button.forEach(function (button) {
        var currentNumber = parseInt(button.dataset.output.match(/\d+/)[0]);
        var newNumber = currentNumber + 1;
        button.dataset.output = button.dataset.output.replace(currentNumber, newNumber);
    });

    // 更新 label 的 for
    var label = lastRow.querySelectorAll("label");
    label.forEach(function (label) {
        var currentNumber = parseInt(label.htmlFor.match(/\d+/)[0]);
        var newNumber = currentNumber + 1;
        label.htmlFor = label.htmlFor.replace(currentNumber, newNumber);
    });

    // 将所有标记删除禁用
    var regex = /delete/;
    var labels = lastRow.getElementsByTagName("input");
    for (var i = 0; i < labels.length; i++) {
        if (regex.test(labels[i].id)) {
            labels[i].disabled = false;
            labels[i].setAttribute("onclick", "onClickDeleteNewTbody(this)");
        }
    }

    // 更新 select 的 id 和 name
    var selects = lastRow.querySelectorAll("select");
    selects.forEach(function (select) {
        var currentNumber = parseInt(select.name.match(/\d+/)[0]);
        var newNumber = currentNumber + 1;
        select.id = select.id.replace(currentNumber, newNumber);
        select.name = select.name.replace(currentNumber, newNumber);

        for (var j = 1; j < select.options.length; j++) {
            if (select.options[j].selected) {
                select.options[0].selected = false;
            }
        }
        select.options[0].selected = true;
    });

    // 在表格末尾添加新行
    tbody.appendChild(lastRow);
    handleSelectChange();
}

function onClickDeleteNewTbody(self) {
    self.closest("tr").remove();
}

function copyTextToClipboard(self) {
    var text = document.getElementById(self.getAttribute("data-copyid"));
    var successBtn;
    if (self.getAttribute("data-successid") !== null)
        successBtn = document.getElementById(self.getAttribute("data-successid"));
    else successBtn = self;
    var originalText = successBtn.innerText;

    text.select();
    text.setSelectionRange(0, 99999); // 兼容移动设备

    var clickboardText;
    if (self.getAttribute("data-orgsub") === "true") {
        clickboardText = text.value + "&original=true";
    } else {
        clickboardText = text.value;
    }

    navigator.clipboard
        .writeText(clickboardText)
        .then(function () {
            successBtn.disabled = true;
            successBtn.innerText = "✓ 复制成功";
            clearTimeout(successBtn.timer);
            successBtn.timer = setTimeout(function () {
                successBtn.innerText = originalText;
                successBtn.disabled = false;
            }, 1000);
        })
        .catch(function (error) {
            console.error("复制失败：", error);
            successBtn.innerText = originalText;
        });
}

function togglePasswordVisibility(self) {
    var passwordInput = document.getElementById(self.getAttribute("data-passid"));
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }
    var iconEye = document.getElementById(self.getAttribute("data-eyeid"));
    if (iconEye.name === "eye-off") {
        iconEye.name = "eye";
    } else {
        iconEye.name = "eye-off";
    }
}

autoDarkMode();
handleSelectChange();
