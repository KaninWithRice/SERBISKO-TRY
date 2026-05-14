<!-- simple-keyboard CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/css/index.css">

<style>
    /* Gboard-inspired Keyboard Styling */
    .keyboard-wrapper {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #d1d4d9;
        padding: 10px 5px;
        box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
        z-index: 9999;
        display: none;
        user-select: none;
        -webkit-user-select: none;
    }

    .keyboard-wrapper.show {
        display: block;
    }

    .simple-keyboard {
        max-width: 1400px;
        margin: 0 auto;
        background-color: transparent;
    }

    .hg-theme-default .hg-button {
        height: 80px;
        font-size: 1.8rem;
        background: #fff;
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        margin: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hg-theme-default .hg-button:active {
        background: #bcc0c4 !important;
    }

    /* Floating Accent Popup */
    .accent-popup {
        position: fixed;
        background: #fff;
        border-radius: 12px;
        display: none;
        padding: 8px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.3);
        z-index: 10000;
        flex-direction: row;
        gap: 8px;
        border: 1px solid #ccc;
    }

    .accent-button {
        width: 75px;
        height: 85px;
        background: #f8f9fa;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        font-weight: bold;
        cursor: pointer;
        color: #333;
        border: 1px solid #ddd;
    }

    .accent-button:active {
        background: #1b5e20;
        color: #fff;
    }

    .close-keyboard {
        position: absolute;
        top: -50px;
        right: 15px;
        background: #3c4043;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 12px 12px 0 0;
        font-weight: bold;
        font-size: 1.2rem;
    }
</style>

<div class="keyboard-wrapper" id="keyboard-container">
    <button class="close-keyboard" onclick="hideKeyboard()">DONE</button>
    <div class="simple-keyboard"></div>
</div>

<div id="accent-popup" class="accent-popup"></div>

<!-- simple-keyboard JS -->
<script src="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/index.js"></script>

<script>
    let keyboard;
    let selectedInput;
    let holdTimer;
    let popupOpen = false;
    let lastFocusTime = 0;

    const accentsMap = {
        'n': ['ñ', 'n'],
        'a': ['á', 'à', 'â', 'ã', 'ä', 'a'],
        'e': ['é', 'è', 'ê', 'ë', 'e'],
        'i': ['í', 'ì', 'î', 'ï', 'i'],
        'o': ['ó', 'ò', 'ô', 'õ', 'ö', 'o'],
        'u': ['ú', 'ù', 'û', 'ü', 'u'],
        'c': ['ç', 'c'],
        'N': ['Ñ', 'N'],
        'A': ['Á', 'À', 'Â', 'Ã', 'Ä', 'A'],
        'E': ['É', 'È', 'Ê', 'Ë', 'E'],
        'I': ['Í', 'Ì', 'Î', 'Ï', 'I'],
        'O': ['Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'O'],
        'U': ['Ú', 'Ù', 'Û', 'Ü', 'U'],
        'C': ['Ç', 'C']
    };

    document.addEventListener("DOMContentLoaded", () => {
        const Keyboard = window.SimpleKeyboard.default;

        keyboard = new Keyboard({
            onChange: input => onChange(input),
            onKeyPress: button => onKeyPress(button),
            onKeyReleased: button => onKeyReleased(button),
            theme: "hg-theme-default",
            disableButtonHold: true,
            layout: {
                default: [
                    "q w e r t y u i o p {bksp}",
                    "a s d f g h j k l {enter}",
                    "{shift} z x c v b n m , . /",
                    "{numbers} {space} {close}"
                ],
                shift: [
                    "Q W E R T Y U I O P {bksp}",
                    "A S D F G H J K L {enter}",
                    "{shift} Z X C V B N M ! ? /",
                    "{numbers} {space} {close}"
                ],
                numbers: [
                    "1 2 3 4 5 6 7 8 9 0 {bksp}",
                    "- / : ; ( ) $ & @ \" {enter}",
                    "{default} . , ? ! '",
                    "{default} {space} {close}"
                ]
            },
            display: {
                "{bksp}": "⌫", "{enter}": "Enter", "{shift}": "⇧", "{space}": "Space",
                "{default}": "ABC", "{numbers}": "123", "{close}": "Close"
            }
        });

        document.addEventListener("focusin", (event) => {
            if (event.target.tagName === "INPUT" && ["text", "password", "email", "number", "tel", "search"].includes(event.target.type)) {
                lastFocusTime = Date.now();
                selectedInput = event.target;
                keyboard.setOptions({ inputName: event.target.name });
                keyboard.setInput(event.target.value);
                handleAutoCapitalization(event.target.value || "");
                showKeyboard();
            }
        });
    });

    function handleAutoCapitalization(input) {
        if (!keyboard) return;
        if (keyboard.options.layoutName === "numbers") return;

        // Auto-capitalize if input is empty, ends with a space, or newline
        const shouldCapitalize = input.length === 0 || input.endsWith(" ") || input.endsWith("\n");

        if (shouldCapitalize) {
            if (keyboard.options.layoutName !== "shift") {
                keyboard.setOptions({ layoutName: "shift" });
            }
        } else {
            if (keyboard.options.layoutName === "shift") {
                keyboard.setOptions({ layoutName: "default" });
            }
        }
    }

    function onChange(input) {
        if (selectedInput) {
            selectedInput.value = input;
            selectedInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        handleAutoCapitalization(input);
    }

    function onKeyPress(button) {
        clearTimeout(holdTimer);
        
        if (popupOpen) {
            hidePopup();
        }

        if (button === "{shift}" || button === "{lock}") {
            let currentLayout = keyboard.options.layoutName;
            keyboard.setOptions({ layoutName: currentLayout === "default" ? "shift" : "default" });
            return;
        }
        if (button === "{numbers}" || button === "{default}") {
            keyboard.setOptions({ layoutName: button === "{numbers}" ? "numbers" : "default" });
            return;
        }
        if (button === "{close}" || button === "{enter}") {
            hideKeyboard();
            return;
        }

        // Long press logic
        if (accentsMap[button]) {
            holdTimer = setTimeout(() => {
                showPopup(button);
            }, 500);
        }
    }

    function onKeyReleased(button) {
        clearTimeout(holdTimer);
    }

    function showPopup(button) {
        popupOpen = true;
        
        // Remove the base character that simple-keyboard just added normally
        let currentInput = keyboard.getInput();
        if (currentInput.endsWith(button)) {
            keyboard.setInput(currentInput.slice(0, -1));
            onChange(keyboard.getInput());
        }

        const accents = accentsMap[button];
        const popup = document.getElementById("accent-popup");
        const buttonElement = keyboard.getButtonElement(button);
        if (!buttonElement) return;

        const rect = buttonElement.getBoundingClientRect();

        popup.innerHTML = "";
        accents.forEach(char => {
            const btn = document.createElement("div");
            btn.className = "accent-button";
            btn.innerText = char;
            
            // Handle click/touch to insert the accent
            const selectAccent = (e) => {
                e.preventDefault();
                e.stopPropagation();
                let currentInput = keyboard.getInput();
                keyboard.setInput(currentInput + char);
                onChange(keyboard.getInput());
                hidePopup();
            };
            btn.onmousedown = selectAccent;
            btn.ontouchstart = selectAccent;
            
            popup.appendChild(btn);
        });

        popup.style.display = "flex";
        popup.style.top = `${rect.top - 100}px`;
        
        const popupWidth = (accents.length * 83) + 16; // 75px width + 8px gap
        let leftPos = rect.left + (rect.width / 2) - (popupWidth / 2);
        if (leftPos < 5) leftPos = 5;
        if (leftPos + popupWidth > window.innerWidth - 5) leftPos = window.innerWidth - popupWidth - 5;
        
        popup.style.left = `${leftPos}px`;
    }

    function hidePopup() {
        document.getElementById("accent-popup").style.display = "none";
        popupOpen = false;
    }

    function showKeyboard() {
        document.getElementById("keyboard-container").classList.add("show");
        document.body.style.paddingBottom = "320px"; 
    }

    function hideKeyboard() {
        document.getElementById("keyboard-container").classList.remove("show");
        document.body.style.paddingBottom = "0";
        hidePopup();
    }

    document.addEventListener("click", event => {
        // Prevent click event right after focus from immediately hiding the keyboard
        if (Date.now() - lastFocusTime < 300) {
            return;
        }

        const isInput = event.target.tagName === "INPUT";
        const isKeyboard = event.target.closest(".keyboard-wrapper");
        const isPopup = event.target.closest(".accent-popup");
        if (!isInput && !isKeyboard && !isPopup) {
            hideKeyboard();
        }
    });

    window.oncontextmenu = function(event) {
        if (event.target.closest(".keyboard-wrapper") || event.target.closest(".accent-popup")) {
            event.preventDefault();
            return false;
        }
    };
</script>
