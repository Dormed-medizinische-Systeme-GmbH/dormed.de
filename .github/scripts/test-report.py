"""Builds the Markdown test report for the PR comment and the job summary.

Usage: python3 test-report.py <junit.xml> <pest-output.txt> <report.md>

Counts come from the JUnit log (test files = distinct "file" attributes
of the <testcase> elements), the coverage table is Pest's own --coverage output, taken from
everything after the "Duration:" line with ANSI colours stripped.
"""

import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

junit_path, output_path, report_path = (Path(arg) for arg in sys.argv[1:4])


def status(passed: int, total: int) -> str:
    icon = "✅" if passed == total else "❌"
    failed = total - passed
    failed_part = f" · **{failed} failed**" if failed else ""
    return f"{icon} **{passed} passes**{failed_part} · {total} total"


lines = ["<!-- test-report -->", "## Pest Test Report", "", "### Summary", ""]

if junit_path.exists():
    root = ET.parse(junit_path).getroot()
    failed_by_file: dict[str, bool] = {}
    tests_total = tests_passed = 0

    for case in root.iter("testcase"):
        failed = case.find("failure") is not None or case.find("error") is not None
        tests_total += 1
        tests_passed += 0 if failed else 1
        # Pest schreibt teils "pfad/Datei.php::testname" ins file-Attribut.
        file = case.get("file") or case.get("class") or "unbekannt"
        file = file.split("::", 1)[0]
        failed_by_file[file] = failed_by_file.get(file, False) or failed

    files_total = len(failed_by_file)
    files_passed = sum(1 for failed in failed_by_file.values() if not failed)

    lines += [
        f"- **Test Files**: {status(files_passed, files_total)}",
        f"- **Test Results**: {status(tests_passed, tests_total)}",
    ]
else:
    lines.append("- ❌ Kein JUnit-Report gefunden, die Testsuite ist vermutlich vor dem Start abgebrochen.")

lines += ["", "### Testabdeckung", ""]

output = output_path.read_text(encoding="utf-8", errors="replace") if output_path.exists() else ""
output = re.sub(r"\x1b\[[0-9;]*m", "", output)
coverage = output.split("Duration:", 1)[1].split("\n", 1)[1] if "Duration:" in output else ""
coverage = "\n".join(line.rstrip() for line in coverage.strip("\n").splitlines())

lines += ["```", coverage, "```"] if coverage.strip() else ["_Keine Coverage-Ausgabe gefunden._"]

report_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
