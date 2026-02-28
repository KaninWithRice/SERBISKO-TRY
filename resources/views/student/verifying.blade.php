<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifying Document...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient {
            background: linear-gradient(180deg, #FFFFFF 0%, #E8F5E9 40%, #1b5e20 100%);
        }
        /* Custom animation for the saving bar */
        @keyframes loadingBar {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        /* CHANGED: Animation now takes 10 seconds (10s) to fill */
        .animate-loading-bar {
            animation: loadingBar 10s ease-in-out forwards;
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4">

    <div id="loading-card" class="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-lg w-full transform transition-all duration-500 scale-100 opacity-100 absolute">
        <h2 class="text-3xl font-bold text-blue-900 mb-2">Analyzing Document...</h2>
        <p class="text-gray-600 mb-8 font-medium">Please wait while the AI checks your records.</p>
        
        <div class="flex justify-center mb-8">
            <svg class="animate-spin h-16 w-16 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <p class="text-sm text-gray-400">This usually takes about 10 to 20 seconds...</p>
    </div>

    <div id="success-card" class="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-lg w-full hidden absolute transform transition-all duration-500 scale-95 opacity-0">
        <div class="flex justify-center mb-6">
            <div class="h-20 w-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        <h2 id="success-title" class="text-3xl font-bold text-green-700 mb-2">Document Verified!</h2>
        <p class="text-gray-600 mb-2 font-medium">Your records have been successfully matched.</p>
    </div>

    <div id="storing-card" class="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-lg w-full hidden absolute transform transition-all duration-500 scale-95 opacity-0">
        <div class="flex justify-center mb-6">
            <div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            </div>
        </div>
        <h2 class="text-3xl font-bold text-blue-900 mb-2">Storing Document...</h2>
        <p class="text-gray-600 mb-6 font-medium">Encrypting and saving to your secure profile.</p>
        
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4 overflow-hidden">
            <div id="progress-bar" class="bg-blue-600 h-3 rounded-full w-0"></div>
        </div>
        <p id="storing-subtext" class="text-sm text-gray-500 font-bold">Please do not close this page...</p>
    </div>

    <div id="error-card" class="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-lg w-full hidden absolute transform transition-all duration-500 scale-95 opacity-0">
        <div class="flex justify-center mb-6">
            <div class="h-20 w-20 bg-red-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>
        <h2 class="text-3xl font-bold text-red-700 mb-2">Scanning Incomplete</h2>
        <p id="error-message" class="text-gray-600 mb-8 font-medium text-lg leading-relaxed">Please reposition the document.</p>
        <button onclick="window.location.href='/student/capture-document'" class="bg-blue-900 text-white font-bold py-3 px-8 rounded-full shadow-md hover:bg-blue-800 transition tracking-wide w-full">
            TRY AGAIN
        </button>
    </div>

    <script>
        let pollInterval;

        function checkStatus() {
            fetch('/student/check-scan-status')
                .then(response => response.json())
                .then(data => {
                    
                    if (data.status === 'verified_lis' || data.status === 'verified') {
                        clearInterval(pollInterval);
                        
                        document.getElementById('success-title').innerText = data.current_doc + " Verified!";
                        
                        if(data.next_url.includes('thankyou')) {
                            document.getElementById('storing-subtext').innerText = "All documents complete! Finishing up...";
                        } else {
                            document.getElementById('storing-subtext').innerText = "Preparing next document scan...";
                        }

                        // STEP 1: Show the Green Success Card
                        showCard('success-card');
                        
                        // STEP 2: After 2 seconds, switch to the "Storing Document" Card
                        setTimeout(() => {
                            showCard('storing-card');
                            
                            // Trigger the CSS loading bar animation
                            document.getElementById('progress-bar').classList.add('animate-loading-bar');
                            
                            // STEP 3: CHANGED TO 10 SECONDS (10000ms)
                            setTimeout(() => {
                                window.location.href = data.next_url; 
                            }, 10000); 
                            
                        }, 2000); // Leaves the green checkmark up for 2 seconds before storing starts
                        
                    } else if (data.status === 'failed_lis' || data.status === 'failed') {
                        clearInterval(pollInterval);
                        showCard('error-card');
                        if(data.remarks) {
                            document.getElementById('error-message').innerText = data.remarks;
                        }
                    }
                })
                .catch(err => console.error("Error checking status:", err));
        }

        function showCard(cardId) {
            const cards = ['loading-card', 'success-card', 'storing-card', 'error-card'];
            
            cards.forEach(id => {
                const el = document.getElementById(id);
                if (!el.classList.contains('hidden') && id !== cardId) {
                    el.classList.remove('scale-100', 'opacity-100');
                    el.classList.add('scale-95', 'opacity-0');
                    setTimeout(() => el.classList.add('hidden'), 500);
                }
            });
            
            setTimeout(() => {
                const target = document.getElementById(cardId);
                target.classList.remove('hidden');
                
                requestAnimationFrame(() => {
                    target.classList.remove('scale-95', 'opacity-0');
                    target.classList.add('scale-100', 'opacity-100');
                });
            }, 500); 
        }

        pollInterval = setInterval(checkStatus, 2000);
    </script>
</body>
</html>