<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Services\Statistics\SessionTrends;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SessionTrendsTest extends TestCase
{
    use RefreshDatabase;

    private ExerciseType $multiplication;

    private ExerciseType $addition;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-12 18:00:00');
        $this->multiplication = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $this->addition = ExerciseType::create(['key' => 'addition', 'name' => 'Plus bis 20']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function exercises(): array
    {
        return ['multiplication' => 'Einmaleins', 'addition' => 'Plus bis 20'];
    }

    /** A finished session whose last answer came $lastAnswerAfter seconds after the start. */
    private function makeRound(Child $child, ExerciseType $type, string $startedAt, int $answered, int $correct, ?int $lastAnswerAfter = 120, array $extra = []): PracticeSession
    {
        $session = PracticeSession::create($extra + [
            'child_id' => $child->id, 'exercise_type_id' => $type->id, 'started_at' => $startedAt,
            'planned_duration_seconds' => 600, 'status' => 'completed',
            'questions_answered' => $answered, 'questions_correct' => $correct,
            // The cron run that closes an abandoned session lands long after the last answer.
            'ended_at' => Carbon::parse($startedAt)->addMinutes(10),
        ]);

        if ($lastAnswerAfter !== null) {
            $fact = Fact::firstOrCreate(
                ['exercise_type_id' => $type->id, 'operand_a' => 2, 'operand_b' => 3],
                ['correct_answer' => 6, 'difficulty_group' => 2],
            );
            $at = Carbon::parse($startedAt)->addSeconds($lastAnswerAfter);
            SessionAttempt::create([
                'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => 6, 'is_correct' => true,
                'response_time_ms' => 1500, 'question_issued_at' => $at->copy()->subSecond(), 'answered_at' => $at,
            ]);
        }

        return $session;
    }

    public function test_correct_and_wrong_tasks_are_summed_per_day(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-10-12 16:00:00', 20, 15);
        $this->makeRound($child, $this->addition, '2026-10-12 17:00:00', 10, 9);
        $this->makeRound($child, $this->multiplication, '2026-10-11 16:00:00', 8, 8);

        $data = app(SessionTrends::class)->build($child, $this->exercises());

        $this->assertCount(60, $data['days']);
        $this->assertSame('2026-10-12', $data['days'][59]['date']);
        $this->assertSame('2026-08-14', $data['days'][0]['date']);

        $all = $data['scopes']['all'];
        $this->assertSame([8, 24], [$all['correct'][58], $all['correct'][59]]);
        $this->assertSame([0, 6], [$all['wrong'][58], $all['wrong'][59]]);

        $mult = $data['scopes']['multiplication'];
        $this->assertSame([8, 15], [$mult['correct'][58], $mult['correct'][59]]);
        $this->assertSame([0, 5], [$mult['wrong'][58], $mult['wrong'][59]]);
    }

    public function test_pace_is_correct_answers_per_minute_from_start_to_last_answer_not_to_the_end_of_the_session(): void
    {
        $child = Child::factory()->create();
        // 15 correct within 3 minutes = 5 per minute; the session itself "ended" after 10 minutes.
        $this->makeRound($child, $this->multiplication, '2026-10-12 16:00:00', 20, 15, lastAnswerAfter: 180);

        $data = app(SessionTrends::class)->build($child, $this->exercises());

        $this->assertSame(5.0, $data['scopes']['multiplication']['tempo'][59]);
        $this->assertSame(5.0, $data['scopes']['multiplication']['rounds'][0]['tempo']);
    }

    public function test_pace_over_several_rounds_is_weighted_by_time(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-10-12 10:00:00', 10, 10, lastAnswerAfter: 60);  // 10 in 1 min
        $this->makeRound($child, $this->multiplication, '2026-10-12 11:00:00', 10, 10, lastAnswerAfter: 240); // 10 in 4 min

        $data = app(SessionTrends::class)->build($child, $this->exercises());

        $this->assertSame(4.0, $data['scopes']['multiplication']['tempo'][59]); // 20 correct in 5 minutes
    }

    public function test_short_sessions_count_for_the_bars_but_not_for_the_pace(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-10-12 16:00:00', 4, 4, lastAnswerAfter: 30);

        $mult = app(SessionTrends::class)->build($child, $this->exercises())['scopes']['multiplication'];

        $this->assertSame(4, $mult['correct'][59]);
        $this->assertNull($mult['tempo'][59]);
        $this->assertNull($mult['rounds'][0]['tempo']);
        $this->assertSame(100, $mult['rounds'][0]['accuracy']);
    }

    public function test_the_pace_is_never_mixed_across_exercises(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-10-12 16:00:00', 20, 20);

        $data = app(SessionTrends::class)->build($child, $this->exercises());

        $this->assertSame(array_fill(0, 60, null), $data['scopes']['all']['tempo']);
        $this->assertNull($data['scopes']['all']['trend']);
    }

    public function test_previews_active_and_empty_sessions_are_left_out(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-10-12 12:00:00', 20, 20, extra: ['is_preview' => true]);
        $this->makeRound($child, $this->multiplication, '2026-10-12 13:00:00', 20, 20, extra: ['status' => 'active']);
        $this->makeRound($child, $this->multiplication, '2026-10-12 14:00:00', 0, 0, lastAnswerAfter: null);
        $other = Child::factory()->create();
        $this->makeRound($other, $this->multiplication, '2026-10-12 15:00:00', 20, 20);

        $all = app(SessionTrends::class)->build($child, $this->exercises())['scopes']['all'];

        $this->assertSame(0, $all['correct'][59]);
        $this->assertSame([], $all['rounds']);
    }

    public function test_sessions_older_than_sixty_days_are_ignored(): void
    {
        $child = Child::factory()->create();
        $this->makeRound($child, $this->multiplication, '2026-08-13 12:00:00', 20, 20);

        $all = app(SessionTrends::class)->build($child, $this->exercises())['scopes']['all'];

        $this->assertSame(0, array_sum($all['correct']));
    }

    public function test_the_trend_compares_the_latest_week_and_month_with_the_one_before(): void
    {
        $child = Child::factory()->create();
        // This week: 10 correct in 2 min = 5/min. The week before: 10 in 5 min = 2/min.
        $this->makeRound($child, $this->multiplication, '2026-10-10 16:00:00', 10, 10, lastAnswerAfter: 120);
        $this->makeRound($child, $this->multiplication, '2026-10-03 16:00:00', 10, 10, lastAnswerAfter: 300);

        $trend = app(SessionTrends::class)->build($child, $this->exercises())['scopes']['multiplication']['trend'];

        $this->assertSame(['current' => 5.0, 'previous' => 2.0], $trend['week']);
        // The month windows span both rounds (this month) and nothing before.
        $this->assertSame(['current' => 2.9, 'previous' => null], $trend['month']);
    }

    public function test_only_the_ten_latest_rounds_are_listed_newest_first(): void
    {
        $child = Child::factory()->create();
        foreach (range(1, 12) as $day) {
            $this->makeRound($child, $this->multiplication, Carbon::parse('2026-10-12 16:00:00')->subDays(12 - $day)->toDateTimeString(), 10, 9);
        }

        $rounds = app(SessionTrends::class)->build($child, $this->exercises())['scopes']['all']['rounds'];

        $this->assertCount(10, $rounds);
        $this->assertSame('12.10. 16:00', $rounds[0]['when']);
        $this->assertSame('03.10. 16:00', $rounds[9]['when']);
        $this->assertSame(['exercise' => 'Einmaleins', 'answered' => 10, 'correct' => 9, 'wrong' => 1, 'accuracy' => 90], [
            'exercise' => $rounds[0]['exercise'], 'answered' => $rounds[0]['answered'], 'correct' => $rounds[0]['correct'],
            'wrong' => $rounds[0]['wrong'], 'accuracy' => $rounds[0]['accuracy'],
        ]);
    }
}
