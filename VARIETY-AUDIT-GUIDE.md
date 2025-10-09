# AI Variety Audit System - User Guide

## Overview
Automated AI-powered validation and enrichment of all 2,959 plant varieties in the database.

**Time Savings**: This system can audit all varieties overnight (~40 hours), saving months of manual checking and research.

## Quick Start

### 1. Test Run (ALWAYS DO THIS FIRST!)
```bash
php artisan varieties:audit --limit=10 --dry-run
```
This will:
- Process first 10 varieties
- Show what issues are found
- NOT make any changes
- Show estimated time for full run

### 2. Check the Results
```bash
# View issues found
cat storage/logs/variety-audit/issues_*.log

# View main log
tail -100 storage/logs/variety-audit/audit_*.log
```

### 3. Run Overnight (Dry-Run First!)
```bash
# WITHOUT auto-fix (safer first run):
nohup php artisan varieties:audit > variety-audit.log 2>&1 &

# WITH auto-fix (after verifying dry-run results):
nohup php artisan varieties:audit --fix > variety-audit.log 2>&1 &

# Check it's running:
tail -f variety-audit.log

# Check progress:
ps aux | grep varieties:audit
```

## Command Options

### Filter by Category
Only audit specific crops:
```bash
# Just broad beans:
php artisan varieties:audit --category="broad bean" --fix

# Just lettuces:
php artisan varieties:audit --category="lettuce" --dry-run

# Just brassicas:
php artisan varieties:audit --category="brassica" --fix
```

### Resume Interrupted Run
If the audit stops, resume from where it left off:
```bash
# Resume from variety ID 1500:
php artisan varieties:audit --start-id=1500 --fix
```

### Process in Batches
Break it into manageable chunks:
```bash
# First 500 varieties:
php artisan varieties:audit --limit=500 --fix

# Next 500 (check previous logs for last ID processed):
php artisan varieties:audit --start-id=501 --limit=500 --fix
```

## What Gets Checked

For each variety, the AI analyzes:

1. **Harvest Notes**
   - Are harvest windows realistic for UK climate?
   - Is timing information missing or generic?
   - Does it match the variety characteristics?

2. **Spacing**
   - In-row spacing appropriate for plant size?
   - Between-row spacing allows proper growth?
   - Missing spacing data flagged

3. **Maturity Days**
   - Seed to harvest time realistic?
   - Missing maturity data flagged
   - Conflicting timing data identified

4. **Planting Method**
   - Should it be direct sown, transplanted, or either?
   - Is the current method appropriate?

5. **General Data Quality**
   - Missing descriptions
   - Placeholder text ("Estimated...", "Please verify...")
   - Obvious errors or inconsistencies

## Understanding Severity Levels

**CRITICAL** - Missing required data
- No harvest notes
- No spacing information
- Critical fields empty

**WARNING** - Questionable data
- Unrealistic spacing (too tight/wide)
- Timing doesn't match variety type
- Generic placeholder text

**INFO** - Minor issues
- Could be improved but not wrong
- Additional detail would help
- Optimization opportunities

## Confidence Levels

**HIGH** - AI is certain (auto-fixable with `--fix`)
- Well-known common varieties
- Standard spacing/timing
- Clear corrections

**MEDIUM** - AI is fairly confident (needs review)
- Less common varieties
- Some uncertainty in recommendations
- Manual verification recommended

**LOW** - AI is unsure (definitely needs expert review)
- Unusual varieties
- Conflicting information
- Needs human expertise

## Output Files

All logs saved to: `storage/logs/variety-audit/`

### Main Audit Log
`audit_YYYY-MM-DD_HH-MM-SS.log`
- Every variety processed
- Step-by-step progress
- Final summary

### Issues Log
`issues_YYYY-MM-DD_HH-MM-SS.log`
- Only varieties with problems
- Severity and confidence levels
- AI suggestions for fixes

### Fixes Log
`fixed_YYYY-MM-DD_HH-MM-SS.log`
- Only created when using `--fix`
- Shows what was changed
- Before/after values

## Example Workflow

### Week 1: Test and Validate
```bash
# Monday: Test on broad beans
php artisan varieties:audit --category="broad bean" --dry-run

# Review results, check they make sense
cat storage/logs/variety-audit/issues_*.log

# Tuesday: Run for real with auto-fix
php artisan varieties:audit --category="broad bean" --fix
```

### Week 2: Category by Category
```bash
# Process each crop family:
php artisan varieties:audit --category="lettuce" --fix
php artisan varieties:audit --category="carrot" --fix
php artisan varieties:audit --category="tomato" --fix
# etc.
```

### Week 3: Full Audit
```bash
# Friday evening: Start full overnight audit
nohup php artisan varieties:audit --fix > variety-audit.log 2>&1 &

# Monday morning: Check results
tail -100 variety-audit.log
cat storage/logs/variety-audit/issues_*.log | grep "CRITICAL"
```

## Monitoring Progress

While running in background:
```bash
# Check if still running:
ps aux | grep varieties:audit

# Watch live progress:
tail -f variety-audit.log

# Count processed so far:
grep "Processing:" storage/logs/variety-audit/audit_*.log | wc -l

# Count issues found:
grep "⚠️" storage/logs/variety-audit/issues_*.log | wc -l
```

## Troubleshooting

### "AI request failed"
- AI service might be down/slow
- Resume with `--start-id=<last_successful_id>`

### Too Many Issues
- First run without `--fix` to review
- May indicate AI needs tuning for specific crops
- Check a few manually to verify AI accuracy

### Slow Performance
- Current: ~48 seconds per variety
- Full run: ~40 hours
- Can run batches of 500 overnight instead

### Want to Stop It
```bash
# Find the process:
ps aux | grep varieties:audit

# Kill it (it's resumable):
kill <process_id>
```

## Safety Features

✅ **Dry-run mode** - Preview without changes
✅ **Confidence thresholds** - Only auto-fix high-confidence items
✅ **Detailed logging** - Every change recorded
✅ **Resumable** - Can stop and restart
✅ **Batch processing** - Can do categories at a time

## Expected Results

Based on initial tests:
- ~30-40% of varieties may have at least one issue flagged
- ~10-15% will have high-confidence auto-fixes
- ~20-25% will need manual review
- ~60-70% will be validated as correct

This means AI can automatically fix ~300-500 varieties and flag ~600-800 for human review, saving months of work!

## Best Practices

1. **Always test first** - Use `--dry-run` and `--limit=10`
2. **Start with one category** - e.g., crops you know well
3. **Review auto-fixes** - Check the fixed log to verify changes
4. **Run overnight** - Takes ~40 hours for all 2,959 varieties
5. **Keep logs** - They're your audit trail
6. **Manual review** - Check CRITICAL and WARNING issues yourself

## Questions?

The audit logs contain all the AI's reasoning. If you're unsure about a suggestion:
1. Look at the variety in the database
2. Check what the AI flagged
3. Verify with seed catalogs or growing guides
4. Trust your expertise over AI when in doubt!
