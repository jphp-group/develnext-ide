package eu.mihosoft.monacofx.model;

import netscape.javascript.JSObject;

public class Selection extends Range {
    public int positionLineNumber;
    public int positionColumn;
    public int selectionStartColumn;
    public int selectionStartLineNumber;

    public Selection() {
    }

    public Selection(JSObject jsObject) {
        super(jsObject);

        positionLineNumber = ((Number) jsObject.getMember("positionLineNumber")).intValue();
        positionColumn = ((Number) jsObject.getMember("positionColumn")).intValue();
        selectionStartColumn = ((Number) jsObject.getMember("selectionStartColumn")).intValue();
        selectionStartLineNumber = ((Number) jsObject.getMember("selectionStartLineNumber")).intValue();
    }

    public Selection(int startLineNumber, int startColumn, int endLineNumber, int endColumn,
                     int positionLineNumber, int positionColumn, int selectionStartColumn, int selectionStartLineNumber) {
        super(startLineNumber, startColumn, endLineNumber, endColumn);
        this.positionLineNumber = positionLineNumber;
        this.positionColumn = positionColumn;
        this.selectionStartColumn = selectionStartColumn;
        this.selectionStartLineNumber = selectionStartLineNumber;
    }

    @Override
    public String toString() {
        return "new monaco.Selection(" + selectionStartLineNumber + ", " + selectionStartColumn + ", " + positionLineNumber + ", " + positionColumn + ")";
    }
}
