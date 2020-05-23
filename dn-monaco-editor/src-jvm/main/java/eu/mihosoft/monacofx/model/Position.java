package eu.mihosoft.monacofx.model;

public class Position {
    private Integer column;
    private Integer lineNumber;

    public Position() {
    }

    public Position(Integer column, Integer lineNumber) {
        this.column = column;
        this.lineNumber = lineNumber;
    }

    public Integer getColumn() {
        return column;
    }

    public void setColumn(Integer column) {
        this.column = column;
    }

    public Integer getLineNumber() {
        return lineNumber;
    }

    public void setLineNumber(Integer lineNumber) {
        this.lineNumber = lineNumber;
    }
}
