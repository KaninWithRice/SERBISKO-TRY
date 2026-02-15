<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Student Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        /* Matching the green gradient background */
        .bg-custom-gradient {
            background: linear-gradient(180deg, #FFFFFF 0%, #E8F5E9 40%, #1b5e20 100%);
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4">

    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-green-900 mb-4">
            What is your <span class="text-blue-900">current student status</span>?
        </h1>
        <p class="text-gray-600 text-lg">
            Please select whether you are a Regular Student, Transferee, Balik-Aral, or an ALS Graduate.
        </p>
    </div>

    <form action="{{ url('/student/save-status') }}" method="POST" class="w-full max-w-6xl">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-16 px-4">
            
            <label class="cursor-pointer group relative">
                <input type="radio" name="student_status" value="regular" class="peer sr-only" required>
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 peer-checked:shadow-xl flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-2xl font-bold text-gray-800 text-center px-4 group-hover:text-blue-900">
                        Regular<br>Student
                    </span>
                </div>
            </label>

            <label class="cursor-pointer group relative">
                <input type="radio" name="student_status" value="transferee" class="peer sr-only">
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 peer-checked:shadow-xl flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-2xl font-bold text-gray-800 text-center px-4 group-hover:text-blue-900">
                        Transferee
                    </span>
                </div>
            </label>

            <label class="cursor-pointer group relative">
                <input type="radio" name="student_status" value="balik_aral" class="peer sr-only">
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 peer-checked:shadow-xl flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-2xl font-bold text-gray-800 text-center px-4 group-hover:text-blue-900">
                        Balik-Aral
                    </span>
                </div>
            </label>

            <label class="cursor-pointer group relative">
                <input type="radio" name="student_status" value="als" class="peer sr-only">
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 peer-checked:shadow-xl flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-2xl font-bold text-gray-800 text-center px-4 group-hover:text-blue-900">
                        ALS<br>Graduate
                    </span>
                </div>
            </label>

        </div>

        <div class="flex justify-center gap-6">
            <a href="{{ url('/student/grade-selection') }}" 
               class="bg-white border-2 border-blue-900 text-blue-900 font-bold text-lg px-16 py-3 rounded-full shadow-lg hover:bg-gray-50 transition transform hover:scale-105 flex items-center justify-center">
                BACK
            </a>

            <button type="submit" 
                    class="bg-blue-900 border-2 border-blue-900 text-white font-bold text-lg px-16 py-3 rounded-full shadow-lg hover:bg-blue-800 transition transform hover:scale-105">
                NEXT
            </button>
        </div>
    </form>

</body>
</html>