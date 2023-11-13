function generateUUID() {
    // 生成一个随机的UUID
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0,
            v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function onClickGenUUID(self) {
    const outputId = self.getAttribute('data-output');
    document.getElementById(outputId).value = generateUUID();
}

function autoDarkMode() {
    const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)"),
        handleDarkModeChange = e => {
            e.matches ? document.documentElement.setAttribute("data-bs-theme", "dark") : document.documentElement.removeAttribute("data-bs-theme")
        };
    handleDarkModeChange(darkModeMediaQuery), darkModeMediaQuery.addListener(handleDarkModeChange);
}

autoDarkMode();