<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\User;
use Excel;    
use App\Imports\StudentsImport;


use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = DB::table('users')
                ->join('students', 'users.id', '=', 'students.userid')
                ->where('roles', 'student')
                ->get();
        return view('admin.sinhvien.index', compact('students'));
    }

    public function getAllStudentsAPI(Request $request) {
        $students = DB::table('users')
        ->join('students', 'users.id', '=', 'students.userid')
        ->where('roles', 'student')
        ->get();
        return $students;
    }

    public function uploadStudentList(Request $request) {
        try {
            //get path
            $path = $request->file('file')->getRealPath();
            Excel::import(new StudentsImport, $path);
        } catch (\Exception $e) {
            \Log::error($e);   
            return back()->with('status-err', 'Upload file thất bại');
        }
        return back()->with('status-success', 'Upload file thành công');
    }

    public function getStudentAPI(Request $request, $studentId) {
        $student = DB::table('users')
        ->join('students', 'users.id', '=', 'students.userid')
        ->where('roles', 'student')
        ->where('students.studentId', $studentId)
        ->get();
        return $student;
    }

    public function edit($id) {
        $student = DB::table('students')
                    ->join('users', 'users.id', '=', 'students.userId')
                    ->where('users.id', $id)
                    ->first();
        return view('admin.sinhvien.edit', compact('student'));
    }


    public function create()
    {
        return view('admin.sinhvien.create');
    }


    public function store(Request $request)
    {
        $data = $request->only([
            'name',
            'dob',
            'class',
            'studentId',
            'email',
        ]);
        try {
            $data['roles'] = 'student';
            $data['password'] = Hash::make($data['studentId']);
            $user = User::create($data);
            $data['userId'] = $user->id;
            $st = DB::table('students')
            ->where('studentId', $data['studentId'])
            ->get(); 
            //check if existed a student has a same stduentId  
            if (count($st) > 0)
                return back()->withInput($data)->with('status', 'Tồn tại mã sinh viên');             
            Student::create($data);

        } catch (\Exception $e) {
            \Log::error($e);   
            return back()->withInput($data)->with('status', 'Tạo sinh viên lỗi');
        }
        
        return redirect('/admin/sinhvien/')
            ->with('status', 'Tạo sinh viên thành công!');
    }

    public function destroy($id) {
        $user = User::findOrFail($id);
        $student = Student::where('userId', $user->id);
        try {
            $student->delete();
            $user->delete();
        } catch (\Exception $e) {
            \Log::error($e);
            return back()->with('status', 'Xóa thất bại');
        }

        return redirect('admin/sinhvien')->with('status', 'Xoá thành công');
    }
}
