package eu.mihosoft.monacofx;

import eu.mihosoft.monacofx.model.Range;

import java.util.List;

public interface CompletionItemProvider {
    List<CompletionItem> complete(RangeWithPosition rangeWithPosition);

    class RangeWithPosition {
        private Range range;
        private Position position;

        public Range getRange() {
            return range;
        }

        public void setRange(Range range) {
            this.range = range;
        }

        public Position getPosition() {
            return position;
        }

        public void setPosition(Position position) {
            this.position = position;
        }
    }

    class CompletionItem {
        private String label;
        private Integer kind; // https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completionitemkind.html
        private String documentation;
        private String insertText;
        private Integer insertTextRules = 1; // https://microsoft.github.io/monaco-editor/api/enums/monaco.languages.completioniteminserttextrule.html

        public CompletionItem() {
        }

        public CompletionItem(String label, Integer kind, String documentation, String insertText) {
            this.label = label;
            this.kind = kind;
            this.documentation = documentation;
            this.insertText = insertText;
        }

        public String getLabel() {
            return label;
        }

        public void setLabel(String label) {
            this.label = label;
        }

        public Integer getKind() {
            return kind;
        }

        public void setKind(Integer kind) {
            this.kind = kind;
        }

        public String getDocumentation() {
            return documentation;
        }

        public void setDocumentation(String documentation) {
            this.documentation = documentation;
        }

        public String getInsertText() {
            return insertText;
        }

        public void setInsertText(String insertText) {
            this.insertText = insertText;
        }

        public Integer getInsertTextRules() {
            return insertTextRules;
        }

        public void setInsertTextRules(Integer insertTextRules) {
            this.insertTextRules = insertTextRules;
        }
    }
}
