$students = \App\Models\Student::with('user')->get();
$count = 0;
foreach ($students as $student) {
    if ($student->user) {
        $student->user->password = \Illuminate\Support\Facades\Hash::make($student->lrn);
        $student->user->save();
        $count++;
    }
}
echo "Reset passwords for " . $count . " students to their LRN.\n";

\Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
\Illuminate\Support\Facades\DB::table('scans')->truncate();
\Illuminate\Support\Facades\DB::table('kiosk_enrollments')->truncate();
\Illuminate\Support\Facades\DB::table('pre_enrollments')->truncate();
\Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Cleared scans, kiosk_enrollments, and pre_enrollments tables.\n";
