<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorting Document...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient { background: linear-gradient(180deg, #FFFFFF 0%, #E8F5E9 40%, #1b5e20 100%); }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl p-12 text-center max-w-2xl w-full mx-4 border border-gray-100 shadow-[8px_8px_0px_0px_rgba(27,94,32,0.8)]">
        <h1 class="text-3xl font-bold text-blue-900 mb-4">Sorting your Document!</h1>
        <p class="text-gray-600 mb-8">Please wait while the machine physically sorts your document into the <span class="font-bold text-green-700">{{ session('cluster') }}</span> bin.</p>
        
        <div class="flex justify-center mb-8">
            <div class="w-16 h-16 border-4 border-blue-900 border-dashed rounded-full animate-spin"></div>
        </div>

        <p class="text-sm text-gray-500 font-bold">Machine operating: <span id="timer" class="text-blue-600 text-lg">30</span> seconds</p>
    </div>

    <script>
        let timeLeft = 30;
        const timerEl = document.getElementById('timer');

        const countdown = setInterval(() => {
            timeLeft--;
            timerEl.innerText = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(countdown);
                // Hardware is done! Move to checklist.
                window.location.href = "{{ url('/student/checklist') }}"; 
            }
        }, 1000);
    </script>
</body>
</html>