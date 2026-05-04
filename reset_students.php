use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;

echo "Starting password reset...\n";
$students = Student::with('user')->get();
$count = 0;
foreach ($students as $student) {
    if ($student->user) {
        $student->user->password = Hash::make($student->lrn);
        $student->user->save();
        $count++;
    }
}
echo "Reset passwords for $count students to their LRN.\n";

echo "Clearing submission history...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('scans')->truncate();
DB::table('kiosk_enrollments')->truncate();
DB::table('pre_enrollments')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Cleared scans, kiosk_enrollments, and pre_enrollments tables.\n";
