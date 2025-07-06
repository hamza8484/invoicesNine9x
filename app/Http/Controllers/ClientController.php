<?php

namespace App\Http\Controllers;

use App\Client; // <== تأكد من المسار الصحيح: App\Models\Client
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <== أضف هذا الاستيراد
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)


class ClientController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول إلى العملاء.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-clients');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = Client::latest()->paginate(10); // عرض 10 عملاء بترتيب الأحدث
        return view('clients.index', compact('clients'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'company'   => 'required|string|max:255',
            'Vat_No'    => 'nullable|string|max:50',
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'notes'     => 'nullable|string',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $client = Client::create([ // <== تأكد من استخدام App\Models\Client
                'name'      => $request->name,
                'company'   => $request->company,
                'Vat_No'    => $request->Vat_No,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'address'   => $request->address,
                'notes'     => $request->notes,
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Client', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $client->id,
                'description' => 'تم إنشاء عميل جديد: ' . $client->name . ' للشركة: ' . $client->company,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($client), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $client->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $client->toArray(), // جميع قيم العميل الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('clients.index')->with('success', 'تم حفظ العميل بنجاح');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating client: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ العميل: ' . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('clients.edit', compact('client'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $oldValues = $client->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name'      => 'required|string|max:255',
            'company'   => 'required|string|max:255',
            'Vat_No'    => 'nullable|string|max:50',
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:50',
            'address'   => 'nullable|string|max:255',
            'notes'     => 'nullable|string',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $client->update([
                'name'      => $request->name,
                'company'   => $request->company,
                'Vat_No'    => $request->Vat_No,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'address'   => $request->address,
                'notes'     => $request->notes,
            ]);

            $newValues = $client->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Client', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $client->id,
                'description' => 'تم تحديث بيانات العميل: ' . $client->name . ' للشركة: ' . $client->company,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($client),
                'auditable_id' => $client->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('clients.index')->with('success', 'تم تحديث بيانات العميل بنجاح');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating client: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث بيانات العميل: ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $oldValues = $client->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $clientId = $client->id;
        $clientName = $client->name;
        $clientCompany = $client->company;

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $client->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Client', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $clientId,
                'description' => 'تم حذف العميل: ' . $clientName . ' للشركة: ' . $clientCompany,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Client', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $clientId,
                'old_values' => $oldValues, // القيم القديمة للعميل الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // <== استخدام request() لجلب IP
                'user_agent' => request()->header('User-Agent'), // <== استخدام request() لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('clients.index')->with('success', 'تم حذف العميل بنجاح');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting client: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف العميل: ' . $e->getMessage());
        }
    }
}
