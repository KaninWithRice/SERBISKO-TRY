<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
            },
          },
        },
      }
    </script>
</head>
<body class="bg-[#F6FFFA] flex min-h-screen overflow-hidden">

    @include('includes.sidebar')

    <main class="flex-1 bg-[#FFFFFF] shadow-2xl flex flex-col relative">
        
        @include('includes.header')

        <div class="px-16 flex-1 overflow-y-auto">
            @yield('content')
        </div>
        
    </main>
</body>
</html>