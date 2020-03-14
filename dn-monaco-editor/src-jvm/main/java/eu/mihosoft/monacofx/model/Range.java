package eu.mihosoft.monacofx.model;

import netscape.javascript.JSObject;

public class Range {
    public int startLineNumber;
    public int startColumn;

    public int endLineNumber;
    public int endColumn;

    public Range() {
    }

    public Range(JSObject jsObject) {
        startLineNumber = ((Number) jsObject.getMember("startLineNumber")).intValue();
        startColumn = ((Number) jsObject.getMember("startColumn")).intValue();
        endLineNumber = ((Number) jsObject.getMember("endLineNumber")).intValue();
        endColumn = ((Number) jsObject.getMember("endColumn")).intValue();
    }

    public Range(int startLineNumber, int startColumn, int endLineNumber, int endColumn) {
        this.startLineNumber = startLineNumber;
        this.startColumn = startColumn;
        this.endLineNumber = endLineNumber;
        this.endColumn = endColumn;
    }

    @Override
    public String toString() {
        return "new monaco.Range(" + startLineNumber + ", " + startColumn + ", " + endLineNumber + ", " + endColumn + ")";
    }
}
