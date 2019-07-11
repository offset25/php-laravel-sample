namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Assignment;
use App\Language;
use App\Skills;
use Auth;
use PragmaRX\Countries\Package\Countries;
class AssignmentController extends Controller
{
    public function index(){
        $user = Auth::user();

        $assignments = Assignment::where(['user_id' => $user->id])->get();
        return view('web.employer.job_listing', compact('assignments'));
    }

    public function search($id) {
        $assignment = Assignment::findOrFail($id);
        return view('web.employer.caregiver_search', compact('assignment'));
    }

    public function edit($id) {
        $assignment = Assignment::findOrFail($id);
        if(Auth::user()->id !== $assignment->user->id){
            return redirect()->route('job.list')->withErrors(array('message' => 'Unathorized'));
        }
        if(isset($assignment->language['id']))
            $assignment->language = array($assignment->language);
        if(isset($assignment->skills['id']))
            $assignment->skills = array($assignment->skills);
        //only get id's
        $assignment->language = array_map(function ($o) {return $o["id"] ?? null;}, $assignment->language ?? []);
        $assignment->skills = array_map(function ($o) {return $o["id"] ?? null;}, $assignment->skills ?? []);

        $languages = Language::all();
        $skills  = Skills::all();
        $states = Countries::where('cca3', 'USA')->first()->hydrateStates()->states->pluck('name');
        return view('web.employer.edit_job', compact('assignment', 'languages', 'skills', 'states'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'title'         => 'required|max:191',
            'description'   => 'required|max:5000',
            'num_days'      => 'required',
            "date_start"    => "required",
            "date_end"      => "required",
            "time_start"    => "required",
            "time_end"      => "required",
            'language'      => 'required',
            "shift_state"   => "required|max:191",
            "shift_zip"     => "required|max:191"
        ]);
//        return $request->all();
        $assignment     = Assignment::findOrFail($id);
        $title          =  $request->input('title');
        $description    =  $request->input('description');
        $num_days       =  $request->input('num_days');
        $skills         =  Skills::find($request->input('skills'));
        $date_start     =  $request->input('date_start');
        $date_end       =  $request->input('date_end');
        $time_start     =  date("H:i", strtotime($request->input('time_start')));
        $time_end       =  date("H:i", strtotime($request->input('time_end')));
        $drive          =  $request->input('drives') == 'on' ? 1 : 0;
        $live_in_shifts =  $request->input('live_in_shifts') == 'on' ? 1 : 0;
        $night_shift    =  $request->input('night_shift') == 'on' ? 1 : 0;
        $network_only   =  $request->input('network_only') == 'on' ? 1 : 0;
        $client_gender  =  $request->input('client_gender');
        $language       =  Language::find($request->input('language'));
        $shift_address1 =  $request->input('shift_address1');
        $shift_address2 =  $request->input('shift_address2');
        $shift_city     =  $request->input('shift_city');
        $shift_state    =  $request->input('shift_state');
        $shift_zip      =  $request->input('shift_zip');
        $rate_details   =  $request->input('rate_details');


        if($title){
            $assignment->title = $title;
        }

        if($description){
            $assignment->description = $description;
        }

        if($num_days){
            $assignment->num_days = $num_days;
        }

        if($skills){
            $assignment->skills = $skills;
        }

        if($date_start){
            $assignment->date_start = $date_start;
        }

        if($date_end){
            $assignment->date_end = $date_end;
        }

        if($time_start){
            $assignment->time_start = $time_start;
        }
        
        if($time_end){
            $assignment->time_end = $time_end;
        }

        if($drive){
            $assignment->drive = $drive;
        }

//        if($night_shift){
//            $assignment->night_shift = $night_shift;
//        }

        if($live_in_shifts){
            $assignment->live_in = $live_in_shifts;
        }

        if($network_only){
            $assignment->private = $network_only;
        }


        if($client_gender){
            $assignment->client_gender = $client_gender;
        }

        if($language){
            $assignment->language = $language;
        }


        if($shift_address1){
            $assignment->shift_address1 = $shift_address1;
        }

        if($shift_address2){
            $assignment->shift_address2 = $shift_address2;
        }

        if($shift_city){
            $assignment->shift_city = $shift_city;
        }

        if($shift_state){
            $assignment->shift_state = $shift_state;
        }

        if($shift_zip){
            $assignment->shift_zip = $shift_zip;
        }

        if($rate_details){
            $assignment->rate_details = $rate_details;
        }
//        return $request->input('state');

//        return $assignment;
//        if (Auth::user()->id !== $assignment->user->id) {
//            return redirect()->route('job.list')->withErrors(array('message' => 'Unauthorized'));
//        }
//        foreach ($request->except(['_token', 'language', 'skills', 'drives', 'night_shift', 'live_in_shifts', 'network_only']) as $key => $input) {
//            $assignment[$key] = $input ?? '';
//        }
//        $assignment['language'] = Language::find($request->input('language'));
//        $assignment->skills = Skills::find($request->input('skills'));
//        $assignment->state = $request->input('shift_state');
//        $assignment->live_in = $request->input('live_in_shifts') == 'on' ? 1 : 0;
//        $assignment->night_shift = $request->input('night_shift') == 'on' ? 1 : 0;
//        $assignment->drive = $request->input('drives') == 'on' ? 1 : 0;
//        $assignment->private = $request->input('network_only') == 'on' ? 1 : 0;

        $assignment->save();
        return redirect()->route('job.list')->withInput()->with('msg','Job Updated Successfully.');
    }

    public function application($id) {
        $assignment = Assignment::findOrFail($id);
        if (Auth::user()->id !== $assignment->user->id) {
            return redirect()->route('job.list')->withErrors(array('message' => 'Unathorized'));
        }
        $assignment->load('applications');

        // return view('web.employer.assignment_applications', compact('assignment'));
        return view('web.employer.assignment', compact('assignment'));
    }
}
