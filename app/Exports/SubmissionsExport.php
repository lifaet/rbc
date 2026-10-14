<?php
namespace App\Exports;
use App\Models\Cycle; use App\Models\Factory; use App\Models\Submission;
use Maatwebsite\Excel\Concerns\FromArray; use Maatwebsite\Excel\Concerns\WithHeadings; use Maatwebsite\Excel\Concerns\WithTitle;
class SubmissionsExport implements FromArray, WithHeadings, WithTitle {
    public function __construct(public ?int $cycleId = null, public ?int $factoryId = null) {}
    public function title(): string { return 'ESG Submissions'; }
    public function headings(): array { return ['Factory','District','Cycle','Category (ESG Pillar)','Question','Question Type','Answer','Submission Status','Submitted At']; }
    public function array(): array {
        $rows=[];
        $subs = Submission::with(['factory','cycle','answers.question.category'])
            ->when($this->cycleId, fn($q)=>$q->where('cycle_id',$this->cycleId))
            ->when($this->factoryId, fn($q)=>$q->where('factory_id',$this->factoryId))->get();
        foreach ($subs as $s) foreach ($s->answers as $a) {
            $v = json_decode($a->value, true); $rows[] = [$s->factory->name,$s->factory->district,$s->cycle->name,($a->question->category->name??'').' ('.($a->question->category->pillar??'').')',$a->question->text,$a->question->type,is_array($v)?implode(', ',$v):$a->value,$s->status,$s->submitted_at?->toDateTimeString()];
        }
        return $rows;
    }
}
