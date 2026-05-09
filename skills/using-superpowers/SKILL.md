---
name: using-superpowers
description: Use when starting any conversation - establishes how to find and use skills, requiring Skill tool invocation before ANY response including clarifying questions
---

<SUBAGENT-STOP>
If you were dispatched as a subagent to execute a specific task, skip this skill.
</SUBAGENT-STOP>

<EXTREMELY-IMPORTANT>
If you think there is even a 1% chance a skill might apply to what you are doing, you ABSOLUTELY MUST invoke the skill.

IF A SKILL APPLIES TO YOUR TASK, YOU DO NOT HAVE A CHOICE. YOU MUST USE IT.

This is not negotiable. This is not optional. You cannot rationalize your way out of this.
</EXTREMELY-IMPORTANT>

## Instruction Priority

1. **User's explicit instructions** (claude.md, GEMINI.md, AGENTS.md, direct requests) — highest priority
2. **Superpowers skills** — override default system behavior where they conflict
3. **Default system prompt** — lowest priority

If claude.md says one thing and a skill says another, follow the user's instructions. The user is in control.

## How to Access Skills

Skills are in `.agents/skills/`. Read the relevant `SKILL.md` before acting.

## The Rule

**Invoke relevant or requested skills BEFORE any response or action.** Even a 1% chance a skill might apply means you should check.

## Available Skills in This Project

| Skill | Trigger |
|-------|---------|
| `karpathy-guidelines` | Any code writing, review, or refactoring |
| `frontend-design` | Building UI, pages, components, styling |
| `find-skills` | Discovering new skills or capabilities |

## Skill Priority

When multiple skills could apply, use this order:
1. **Process skills first** (brainstorming, debugging) — determine HOW to approach
2. **Implementation skills second** (frontend-design) — guide execution

## Red Flags — STOP if you think:

| Thought | Reality |
|---------|---------|
| "This is just a simple question" | Questions are tasks. Check for skills. |
| "I need more context first" | Skill check comes BEFORE clarifying questions. |
| "This doesn't need a formal skill" | If a skill exists, use it. |
| "I'll just do this one thing first" | Check BEFORE doing anything. |
| "I know what that means" | Knowing the concept ≠ using the skill. Invoke it. |

## Skill Types

**Rigid** (TDD, debugging): Follow exactly. Don't adapt away discipline.

**Flexible** (patterns): Adapt principles to context.

The skill itself tells you which type it is.
