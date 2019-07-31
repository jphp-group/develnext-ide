package org.develnext.ide;

import javax.swing.*;
import java.io.*;
import java.net.URISyntaxException;
import java.util.*;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

public class Launcher {
    public static final String[] defaultJvmArgs = {
            "-Xms256M", "-XX:ReservedCodeCacheSize=150m", "-XX:+UseConcMarkSweepGC", "-Dsun.io.useCanonCaches=false",
            "-Djava.net.preferIPv4Stack=true", "-Dfile.encoding=UTF-8", "-Ddevelnext.launcher=root"
    };

    protected ProcessBuilder processBuilder;
    protected Process process;
    private File rootDir;

    protected String[] fetchJvmArgs() {
        String[] jvmArgs = defaultJvmArgs;

        if (new File(rootDir, "/DevelNext.conf").exists()) {
            try {
                Scanner scanner = new Scanner(new FileInputStream(new File(rootDir, "/DevelNext.conf")), "UTF-8");

                Set<String> newJvmArgs = new TreeSet<String>();

                while (scanner.hasNextLine()) {
                    String line = scanner.nextLine().trim();

                    if (line.isEmpty() || line.startsWith("#")) {
                        continue;
                    }

                    newJvmArgs.add(line);
                }

                jvmArgs = newJvmArgs.toArray(new String[newJvmArgs.size()]);

            } catch (FileNotFoundException e) {
                e.printStackTrace();
            }
        }

        return jvmArgs;
    }

    private static String[] concatArrays(String[] first, String[] second) {
        List<String> both = new ArrayList<String>(first.length + second.length);
        Collections.addAll(both, first);
        Collections.addAll(both, second);
        return both.toArray(new String[both.size()]);
    }

    public boolean isValidJava() {
        return new Version(System.getProperty("java.version").replace("_", ".")).compareTo(new Version("11.0.0")) == 1;
    }

    public void start() throws URISyntaxException, IOException, InterruptedException {
        if (!isValidJava()) {
            JOptionPane.showMessageDialog(null, "Open Java Runtime 11+ required", "Error", JOptionPane.ERROR_MESSAGE);
            return;
        }

        rootDir = new File(Launcher.class.getProtectionDomain().getCodeSource().getLocation().toURI().getPath()).getParentFile();

        String[] jvmArgs = fetchJvmArgs();

        String javaBin = "java";
        String java_home = System.getenv("JAVA_HOME");

        if (!Boolean.getBoolean("dn.dontUseLocalJre")) {
            if (new File(rootDir, "/jre/bin").isDirectory()) {
                java_home = new File(rootDir, "/jre/").getAbsolutePath();
            }
        }

        if (java_home != null) {
            if (new File(java_home, "/bin/java.exe").isFile()) {
                javaBin = new File(java_home, "/bin/java.exe").getCanonicalPath();
            } else if (new File(java_home, "/bin/java").isFile()) {
                javaBin = new File(java_home, "/bin/java").getCanonicalPath();
            }
        }

        jvmArgs = concatArrays(new String[]{ javaBin }, jvmArgs);

        StringBuilder classPaths = new StringBuilder(
                new File(rootDir.getAbsolutePath() + "/libs/*").getAbsolutePath()
        );

        File[] sources = new File(rootDir, "/sources").listFiles(File::isDirectory);

        if (sources != null) {
            for (File source : sources) {
                if ("platforms".equals(source.getName())) {
                    File[] platforms = source.listFiles(File::isDirectory);
                    for (File platform : platforms) {
                        classPaths.append(File.pathSeparator).append(platform.getAbsolutePath());
                    }
                } else {
                    classPaths.append(File.pathSeparator).append(source.getAbsolutePath());
                }
            }
        }

        if (new File(rootDir, "/vendor/paths.json").isFile()) {
            JSONParser parser = new JSONParser();
            try {
                Object obj = parser.parse(new FileReader(new File(rootDir, "/vendor/paths.json")));

                JSONObject jsonObject = (JSONObject) obj;

                if (jsonObject.containsKey("classPaths")) {
                    jsonObject = (JSONObject) jsonObject.get("classPaths");

                    if (jsonObject.containsKey("")) {
                        // loop array
                        JSONArray classPathsJson = (JSONArray) jsonObject.get("");
                        Iterator<String> iterator = classPathsJson.iterator();
                        while (iterator.hasNext()) {
                            classPaths.append(File.pathSeparator)
                                    .append(new File(rootDir, "/vendor/" + iterator.next()).getAbsolutePath());
                        }
                    }
                }
            } catch (ParseException|IOException e) {
                e.printStackTrace();
                System.exit(1);
            }
        }

        jvmArgs = concatArrays(jvmArgs, new String[] { "-cp", classPaths.toString() });

        String[] args = concatArrays(jvmArgs, new String[]{
                "-Ddevelnext.launcher=root",
                "-Denvironment=" + System.getProperty("environment", "prod"),
                "-Ddevelnext.path=" + rootDir.getAbsolutePath(), "org.develnext.jphp.ext.javafx.FXLauncher"
        });

        System.out.println(join(args, " "));

        processBuilder = new ProcessBuilder(args);
        int exit = processBuilder.inheritIO().start().waitFor();
        System.exit(exit);
    }

    public static void main(String[] args) throws URISyntaxException, IOException, InterruptedException {
        new Launcher().start();
    }

    public static String join(Object[] array, String separator, int startIndex, int endIndex) {
        if (array == null) {
            return null;
        }
        if (separator == null) {
            separator = "";
        }

        // endIndex - startIndex > 0:   Len = NofStrings *(len(firstString) + len(separator))
        //           (Assuming that all Strings are roughly equally long)
        int noOfItems = endIndex - startIndex;
        if (noOfItems <= 0) {
            return "";
        }

        StringBuilder buf = new StringBuilder(noOfItems * 16);

        for (int i = startIndex; i < endIndex; i++) {
            if (i > startIndex) {
                buf.append(separator);
            }
            if (array[i] != null) {
                buf.append(array[i]);
            }
        }
        return buf.toString();
    }

    public static String join(Object[] array, String separator) {
        if (array == null) {
            return null;
        }
        return join(array, separator, 0, array.length);
    }
}
