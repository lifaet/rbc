<?php
namespace App\Http\Controllers;
use App\Exports\SubmissionsExport; use App\Models\Cycle; use App\Models\Factory;
use Barryvdh\DomPDF\Facade\Pdf; use Illuminate\Http\Request; use Maatwebsite\Excel\Facades\Excel;
class ExportController extends Controller {
    public function submissionsExcel(Request $request) { return Excel::download(new SubmissionsExport($request->integer('cycle') ?: null), 'esg-submissions.xlsx'); }
    public function factoryExcel(Factory $factory, Request $request) { return Excel::download(new SubmissionsExport($request->integer('cycle') ?: null, $factory->id), 'esg-factory-'.$factory->id.'.xlsx'); }
    public function factoryPdf(Factory $factory, Request $request) {
        $cycleId = $request->integer('cycle') ?: Cycle::latest('open_date')->value('id');
        $submission = $factory->submissions()->where('cycle_id', $cycleId)->with(['cycle', 'answers.question.category'])->first();
        return Pdf::loadView('exports.factory-pdf', ['factory' => $factory, 'submission' => $submission])->download('esg-factory-'.$factory->id.'.pdf');
    }
}
