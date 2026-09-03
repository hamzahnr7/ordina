# Critique Exercise (DESIGN-04)

The assessor supplies a deliberately-flawed code snippet during technical
defense. Do not implement a fix - write the analysis only:

1. **Smell(s) present** - name them (e.g. Long Method, Feature Envy, God
   Class doing validation + persistence + notification at once).
2. **SOLID principle(s) violated** - which one(s), and the specific line/
   responsibility that violates it.
3. **How it should be refactored** - target structure (e.g. split into a
   Validator, a Repository call, and a Notifier collaborator invoked by a
   thin orchestrating method), without necessarily writing the full code.

_Fill this in when the snippet is provided at defense time._
