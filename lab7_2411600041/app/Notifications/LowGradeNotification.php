<?php

namespace App\Notifications;

use App\Models\GradeItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowGradeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $gradeItemId;
    public string $subjectCode;
    public string $subjectName;
    public float $grade;
    public float $threshold;

    public function __construct(GradeItem $gradeItem)
    {
        $this->gradeItemId = $gradeItem->id;
        $this->subjectCode = $gradeItem->subject_code;
        $this->subjectName = $gradeItem->subject_name;
        $this->grade = (float) $gradeItem->current_grade;
        $this->threshold = (float) $gradeItem->low_grade_threshold;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("At Risk: {$this->subjectCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->subjectCode} - {$this->subjectName} is now At Risk.")
            ->line("Current grade: {$this->grade} (threshold: {$this->threshold})")
            ->action('View Subject', route('grade-items.show', $this->gradeItemId))
            ->line('Review this subject and plan how to improve the grade.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'grade_item_id' => $this->gradeItemId,
            'subject_code' => $this->subjectCode,
            'subject_name' => $this->subjectName,
            'current_grade' => $this->grade,
            'threshold' => $this->threshold,
            'message' => "{$this->subjectCode} is At Risk: grade {$this->grade} is below the {$this->threshold} threshold.",
            'action' => 'Review this subject and plan how to improve the grade.',
        ];
    }
}